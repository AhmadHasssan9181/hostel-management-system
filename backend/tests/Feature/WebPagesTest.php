<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Sign in to HMS');
    }

    public function test_a_student_can_sign_in_and_open_every_portal_page(): void
    {
        $student = $this->makeStudent(['email' => 'student@hms.test']);
        $room = $this->makeRoom($this->makeHostel());
        $booking = $this->allocate($student, $room);

        Complaint::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'category' => 'wifi',
            'description' => 'The Wi-Fi keeps disconnecting on the second floor.',
        ]);

        $this->post('/login', [
            'email' => 'student@hms.test',
            'password' => 'Password@123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($student);

        $this->get('/dashboard')->assertOk()->assertSee('Student portal');
        $this->get('/rooms')->assertOk();
        $this->get('/rooms/'.$room->id)->assertOk()->assertSee('Room '.$room->room_number)->assertSee('About this room');
        $this->get('/bookings')->assertOk()->assertSee('Room '.$room->room_number);
        $this->get('/complaints')->assertOk();
        $this->get('/complaints/'.Complaint::first()->id)->assertOk()->assertSee('Progress');
        $this->get('/fees')->assertOk();
        $this->get('/room-swaps')->assertOk();
        $this->get('/requests')->assertOk();
        $this->get('/facilities')->assertOk();
        $this->get('/notifications')->assertOk();
        $this->get('/profile')->assertOk()->assertSee($student->email);

        $this->assertSame($booking->id, $booking->fresh()->id);
    }

    public function test_an_employee_can_open_the_staff_workspace(): void
    {
        $employee = $this->makeUser(Role::Employee, ['email' => 'warden@hms.test']);
        $student = $this->makeStudent();
        $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $this->post('/login', [
            'email' => 'warden@hms.test',
            'password' => 'Password@123',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk()->assertSee('Staff workspace');

        foreach (['/students', '/students/'.$student->id, '/hostels', '/rooms', '/bookings', '/complaints', '/fees', '/room-swaps', '/requests', '/facilities', '/inventory'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/users')->assertForbidden();
    }

    public function test_an_administrator_can_open_user_management(): void
    {
        $admin = $this->makeUser(Role::Admin, ['email' => 'admin@hms.test']);

        $this->actingAs($admin)->get('/users')->assertOk()->assertSee('User accounts');
    }

    public function test_a_student_can_request_a_room_through_the_web_form(): void
    {
        $student = $this->makeStudent();
        $room = $this->makeRoom($this->makeHostel());

        $this->actingAs($student)
            ->post('/bookings', ['room_id' => $room->id])
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'student_id' => $student->id,
            'room_id' => $room->id,
            'status' => BookingStatus::Pending->value,
        ]);
    }

    public function test_staff_can_approve_a_booking_through_the_web_ui(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();
        $room = $this->makeRoom($this->makeHostel(), ['capacity' => 1]);
        $booking = Booking::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->post('/bookings/'.$booking->id.'/approve')
            ->assertRedirect();

        $this->assertSame(BookingStatus::Approved, $booking->fresh()->status);
        $this->assertSame(1, $room->fresh()->occupied_beds);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new@hms.test',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'registration_number' => 'REG-WEB-001',
            'department' => 'Computer Science',
            'semester' => 3,
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'new@hms.test']);
    }

    public function test_invalid_credentials_return_an_error(): void
    {
        $this->makeStudent(['email' => 'student@hms.test']);

        $this->post('/login', [
            'email' => 'student@hms.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
