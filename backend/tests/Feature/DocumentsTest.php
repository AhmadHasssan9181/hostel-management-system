<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\Role;
use App\Models\Complaint;
use App\Models\Fee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_a_student_can_print_their_own_fee_voucher_and_receipt(): void
    {
        $student = $this->makeStudent();
        $fee = $this->makeFee($student, FeeStatus::Unpaid);

        $this->actingAs($student)
            ->get('/fees/'.$fee->id.'/voucher')
            ->assertOk()
            ->assertSee('Hostel Fee Voucher')
            ->assertSee($fee->reference)
            ->assertSee('Student copy')
            ->assertSee('Bank copy')
            ->assertSee('Office copy');

        $fee->update(['status' => FeeStatus::Paid, 'paid_at' => now()]);

        $this->actingAs($student)
            ->get('/fees/'.$fee->id.'/voucher')
            ->assertOk()
            ->assertSee('Verified');
    }

    public function test_a_student_cannot_print_someone_elses_voucher(): void
    {
        $owner = $this->makeStudent();
        $other = $this->makeStudent();
        $fee = $this->makeFee($owner, FeeStatus::Unpaid);

        $this->actingAs($other)
            ->get('/fees/'.$fee->id.'/voucher')
            ->assertForbidden();
    }

    public function test_staff_can_batch_print_unpaid_vouchers(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $this->makeFee($this->makeStudent(), FeeStatus::Unpaid);
        $this->makeFee($this->makeStudent(), FeeStatus::Overdue);

        $this->actingAs($employee)
            ->get('/fees/vouchers/batch')
            ->assertOk()
            ->assertSee('Hostel Fee Voucher')
            ->assertSee('print-page');
    }

    public function test_students_cannot_batch_print_vouchers(): void
    {
        $this->actingAs($this->makeStudent())
            ->get('/fees/vouchers/batch')
            ->assertForbidden();
    }

    public function test_an_allocation_slip_is_printable_by_the_owner(): void
    {
        $student = $this->makeStudent();
        $booking = $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $this->actingAs($student)
            ->get('/bookings/'.$booking->id.'/slip')
            ->assertOk()
            ->assertSee('Room Allocation Slip')
            ->assertSee($student->name);

        $this->actingAs($this->makeStudent())
            ->get('/bookings/'.$booking->id.'/slip')
            ->assertForbidden();
    }

    public function test_a_complaint_ticket_is_printable_by_the_owner(): void
    {
        $student = $this->makeStudent();
        $booking = $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $complaint = Complaint::create([
            'student_id' => $student->id,
            'room_id' => $booking->room_id,
            'category' => 'plumbing',
            'description' => 'The bathroom tap is leaking badly.',
        ]);

        $this->actingAs($student)
            ->get('/complaints/'.$complaint->id.'/ticket')
            ->assertOk()
            ->assertSee('Maintenance Ticket')
            ->assertSee($complaint->ticket_no);

        $this->actingAs($this->makeStudent())
            ->get('/complaints/'.$complaint->id.'/ticket')
            ->assertForbidden();
    }

    private function makeFee($student, FeeStatus $status): Fee
    {
        return Fee::create([
            'reference' => 'FEE-DOC-'.uniqid(),
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'period' => now()->format('Y-m'),
            'amount' => 6500,
            'fine' => 500,
            'status' => $status,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);
    }
}
