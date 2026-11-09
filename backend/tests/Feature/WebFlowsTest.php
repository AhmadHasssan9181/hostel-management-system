<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ComplaintStatus;
use App\Enums\FeeStatus;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Fee;
use App\Models\RoomSwapRequest;
use App\Models\StudentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class WebFlowsTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_booking_can_be_cancelled_and_rejected(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);
        $hostel = $this->makeHostel();

        $cancelled = Booking::create([
            'student_id' => $student->id,
            'room_id' => $this->makeRoom($hostel)->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($student)
            ->post('/bookings/'.$cancelled->id.'/cancel')
            ->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $cancelled->fresh()->status);

        $rejected = Booking::create([
            'student_id' => $this->makeStudent()->id,
            'room_id' => $this->makeRoom($hostel)->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->post('/bookings/'.$rejected->id.'/reject', ['review_note' => 'No space'])
            ->assertRedirect();

        $this->assertSame(BookingStatus::Rejected, $rejected->fresh()->status);
        $this->assertSame('No space', $rejected->fresh()->review_note);
    }

    public function test_check_in_and_check_out_update_the_resident_and_bed_count(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();
        $room = $this->makeRoom($this->makeHostel(), ['capacity' => 2]);
        $booking = $this->allocate($student, $room);

        $this->actingAs($employee)
            ->post('/bookings/'.$booking->id.'/check-in')
            ->assertRedirect();

        $this->assertSame('checked_in', $student->fresh()->studentProfile->check_in_status);

        $this->actingAs($employee)
            ->post('/bookings/'.$booking->id.'/check-out')
            ->assertRedirect();

        $this->assertSame(BookingStatus::CheckedOut, $booking->fresh()->status);
        $this->assertSame(0, $room->fresh()->occupied_beds);
    }

    public function test_a_complaint_can_be_assigned_and_resolved(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);
        $booking = $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $this->actingAs($student)->post('/complaints', [
            'category' => 'plumbing',
            'priority' => 'high',
            'description' => 'The shower drain is blocked and water pools on the floor.',
        ])->assertRedirect();

        $complaint = Complaint::firstOrFail();
        $this->assertSame($booking->room_id, $complaint->room_id);

        $this->actingAs($employee)
            ->post('/complaints/'.$complaint->id.'/assign', ['assigned_to' => $employee->id])
            ->assertRedirect();

        $this->assertSame(ComplaintStatus::InProgress, $complaint->fresh()->status);

        $this->actingAs($employee)
            ->patch('/complaints/'.$complaint->id.'/status', [
                'status' => 'resolved',
                'resolution_note' => 'Drain cleared.',
            ])->assertRedirect();

        $this->assertSame(ComplaintStatus::Resolved, $complaint->fresh()->status);
        $this->assertNotNull($complaint->fresh()->resolved_at);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $student->id, 'type' => 'complaint']);
    }

    public function test_fee_generation_payment_verification_and_waiver(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();

        $this->actingAs($employee)->post('/fees', [
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'period' => now()->format('Y-m'),
            'amount' => 6500,
            'fine' => 200,
            'due_date' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $fee = Fee::firstOrFail();
        $this->assertSame(FeeStatus::Unpaid, $fee->status);

        $this->actingAs($student)->post('/fees/'.$fee->id.'/pay', [
            'payment_method' => 'easypaisa',
        ])->assertRedirect();

        $this->assertSame(FeeStatus::PendingVerification, $fee->fresh()->status);

        $this->actingAs($employee)->post('/fees/'.$fee->id.'/verify', [
            'approved' => '1',
        ])->assertRedirect();

        $this->assertSame(FeeStatus::Paid, $fee->fresh()->status);
        $this->assertNotNull($fee->fresh()->paid_at);

        $this->actingAs($employee)->post('/fees', [
            'student_id' => $student->id,
            'type' => 'fine',
            'amount' => 500,
            'due_date' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $second = Fee::where('type', 'fine')->firstOrFail();

        $this->actingAs($employee)
            ->post('/fees/'.$second->id.'/waive', ['note' => 'First offence'])
            ->assertRedirect();

        $this->assertSame(FeeStatus::Waived, $second->fresh()->status);
    }

    public function test_a_rejected_payment_returns_to_unpaid(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();
        $fee = Fee::create([
            'reference' => 'FEE-REJECT-1',
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'status' => FeeStatus::PendingVerification,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($employee)->post('/fees/'.$fee->id.'/verify', [
            'approved' => '0',
            'note' => 'Receipt unreadable',
        ])->assertRedirect();

        $this->assertSame(FeeStatus::Unpaid, $fee->fresh()->status);
    }

    public function test_room_swap_requests_can_be_approved_rejected_and_cancelled(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $hostel = $this->makeHostel();

        $student = $this->makeStudent();
        $current = $this->makeRoom($hostel, ['room_number' => 'S1', 'capacity' => 2]);
        $target = $this->makeRoom($hostel, ['room_number' => 'S2', 'capacity' => 2]);
        $this->allocate($student, $current);

        $this->actingAs($student)->post('/room-swaps', [
            'requested_room_id' => $target->id,
            'reason' => 'I need a lower floor because of a knee injury.',
        ])->assertRedirect();

        $swap = RoomSwapRequest::firstOrFail();

        $this->actingAs($employee)
            ->post('/room-swaps/'.$swap->id.'/approve')
            ->assertRedirect();

        $this->assertSame(RequestStatus::Approved, $swap->fresh()->status);
        $this->assertSame($target->id, $student->fresh()->activeBooking->room_id);
        $this->assertSame(1, $target->fresh()->occupied_beds);

        $secondStudent = $this->makeStudent();
        $secondCurrent = $this->makeRoom($hostel, ['room_number' => 'S3']);
        $secondTarget = $this->makeRoom($hostel, ['room_number' => 'S4']);
        $this->allocate($secondStudent, $secondCurrent);

        $this->actingAs($secondStudent)->post('/room-swaps', [
            'requested_room_id' => $secondTarget->id,
            'reason' => 'Looking to move closer to the study hall.',
        ])->assertRedirect();

        $secondSwap = RoomSwapRequest::where('student_id', $secondStudent->id)->firstOrFail();

        $this->actingAs($employee)
            ->post('/room-swaps/'.$secondSwap->id.'/reject', ['review_note' => 'No vacancy'])
            ->assertRedirect();

        $this->assertSame(RequestStatus::Rejected, $secondSwap->fresh()->status);

        $thirdStudent = $this->makeStudent();
        $this->allocate($thirdStudent, $this->makeRoom($hostel));
        $thirdTarget = $this->makeRoom($hostel);

        $this->actingAs($thirdStudent)->post('/room-swaps', [
            'requested_room_id' => $thirdTarget->id,
            'reason' => 'Prefer a room on the ground floor.',
        ])->assertRedirect();

        $thirdSwap = RoomSwapRequest::where('student_id', $thirdStudent->id)->firstOrFail();

        $this->actingAs($thirdStudent)
            ->post('/room-swaps/'.$thirdSwap->id.'/cancel')
            ->assertRedirect();

        $this->assertSame(RequestStatus::Cancelled, $thirdSwap->fresh()->status);
    }

    public function test_student_requests_can_be_reviewed_and_cancelled(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);

        $this->actingAs($student)->post('/requests', [
            'type' => 'leave',
            'subject' => 'Weekend leave',
            'details' => 'Visiting family.',
            'from_date' => now()->toDateString(),
            'to_date' => now()->addDays(2)->toDateString(),
        ])->assertRedirect();

        $request = StudentRequest::firstOrFail();

        $this->actingAs($employee)->post('/requests/'.$request->id.'/review', [
            'status' => 'approved',
        ])->assertRedirect();

        $this->assertSame(RequestStatus::Approved, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->reviewed_at);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $student->id, 'type' => 'request']);

        $this->actingAs($student)->post('/requests', [
            'type' => 'other',
            'subject' => 'Guest visit',
        ])->assertRedirect();

        $second = StudentRequest::where('subject', 'Guest visit')->firstOrFail();

        $this->actingAs($student)->post('/requests/'.$second->id.'/cancel')->assertRedirect();
        $this->assertSame(RequestStatus::Cancelled, $second->fresh()->status);
    }

    public function test_staff_can_edit_a_booking_and_move_it_to_another_room(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $hostel = $this->makeHostel();
        $student = $this->makeStudent();

        $oldRoom = $this->makeRoom($hostel, ['capacity' => 2]);
        $newRoom = $this->makeRoom($hostel, ['capacity' => 2]);
        $booking = $this->allocate($student, $oldRoom);

        $this->actingAs($employee)->patch('/bookings/'.$booking->id, [
            'room_id' => $newRoom->id,
            'note' => 'Moved closer to the study hall.',
        ])->assertRedirect();

        $booking->refresh();

        $this->assertSame($newRoom->id, $booking->room_id);
        $this->assertSame('Moved closer to the study hall.', $booking->note);
        $this->assertSame(0, $oldRoom->fresh()->occupied_beds);
        $this->assertSame(1, $newRoom->fresh()->occupied_beds);
    }

    public function test_staff_can_edit_an_invoice(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();

        $fee = Fee::create([
            'reference' => 'FEE-EDIT-1',
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($employee)->patch('/fees/'.$fee->id, [
            'type' => 'mess_fee',
            'period' => '2026-12',
            'amount' => 7200,
            'fine' => 300,
            'due_date' => now()->addDays(12)->toDateString(),
        ])->assertRedirect();

        $fee->refresh();

        $this->assertSame('mess_fee', $fee->type);
        $this->assertSame('7200.00', $fee->amount);
        $this->assertSame('2026-12', $fee->period);
    }

    public function test_a_paid_invoice_cannot_be_edited(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();

        $fee = Fee::create([
            'reference' => 'FEE-PAID-1',
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'status' => FeeStatus::Paid,
            'due_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);

        $this->actingAs($employee)->patch('/fees/'.$fee->id, [
            'type' => 'hostel_fee',
            'amount' => 100,
            'due_date' => now()->toDateString(),
        ])->assertSessionHas('error');

        $this->assertSame('6500.00', $fee->fresh()->amount);
    }

    public function test_an_admin_can_edit_a_paid_invoice(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $student = $this->makeStudent();

        $fee = Fee::create([
            'reference' => 'FEE-PAID-2',
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'status' => FeeStatus::Paid,
            'due_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)->patch('/fees/'.$fee->id, [
            'type' => 'hostel_fee',
            'amount' => 6000,
            'fine' => 0,
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('6000.00', $fee->fresh()->amount);
    }

    public function test_staff_can_edit_a_complaint(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();
        $booking = $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $complaint = Complaint::create([
            'student_id' => $student->id,
            'room_id' => $booking->room_id,
            'category' => 'wifi',
            'priority' => 'normal',
            'description' => 'Wi-Fi is slow in the evening.',
        ]);

        $this->actingAs($employee)->patch('/complaints/'.$complaint->id, [
            'category' => 'electrical',
            'priority' => 'urgent',
            'description' => 'The power socket near the desk sparks when used.',
        ])->assertRedirect();

        $complaint->refresh();

        $this->assertSame('electrical', $complaint->category);
        $this->assertSame('urgent', $complaint->priority->value);
        $this->assertStringContainsString('sparks', $complaint->description);
    }

    public function test_password_reset_flow_works_through_the_web_forms(): void
    {
        $student = $this->makeStudent(['email' => 'reset@hms.test']);

        $this->post('/forgot-password', ['email' => 'reset@hms.test'])
            ->assertSessionHas('success');

        $token = Password::createToken($student);

        $this->post('/reset-password', [
            'email' => 'reset@hms.test',
            'token' => $token,
            'password' => 'ResetPass@123',
            'password_confirmation' => 'ResetPass@123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('ResetPass@123', $student->fresh()->password));
    }
}
