<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\RoomStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_a_student_can_request_a_room_and_staff_can_approve_it(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);
        $room = $this->makeRoom($this->makeHostel(), ['capacity' => 1]);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/bookings', ['room_id' => $room->id]);

        $response->assertCreated()
            ->assertJsonPath('booking.status', 'pending');

        $bookingId = $response->json('booking.id');

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/bookings/{$bookingId}/approve", ['review_note' => 'Welcome'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'approved');

        $room->refresh();
        $this->assertSame(1, $room->occupied_beds);
        $this->assertSame(RoomStatus::Full, $room->status);
    }

    public function test_a_room_cannot_be_overbooked(): void
    {
        $hostel = $this->makeHostel();
        $employee = $this->makeUser(Role::Employee);
        $room = $this->makeRoom($hostel, ['capacity' => 1]);

        $first = $this->makeStudent();
        $second = $this->makeStudent();

        $this->allocate($first, $room);

        $this->actingAs($second, 'sanctum')
            ->postJson('/api/v1/bookings', ['room_id' => $room->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');

        $pending = Booking::create([
            'student_id' => $second->id,
            'room_id' => $room->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/bookings/{$pending->id}/approve")
            ->assertUnprocessable();
    }

    public function test_a_student_cannot_hold_two_active_requests(): void
    {
        $student = $this->makeStudent();
        $hostel = $this->makeHostel();
        $roomA = $this->makeRoom($hostel);
        $roomB = $this->makeRoom($hostel);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/bookings', ['room_id' => $roomA->id])
            ->assertCreated();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/bookings', ['room_id' => $roomB->id])
            ->assertUnprocessable();
    }

    public function test_checkout_releases_the_bed(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);
        $room = $this->makeRoom($this->makeHostel(), ['capacity' => 2]);
        $booking = $this->allocate($student, $room);

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/bookings/{$booking->id}/check-out")
            ->assertOk()
            ->assertJsonPath('booking.status', 'checked_out');

        $this->assertSame(0, $room->fresh()->occupied_beds);
        $this->assertSame(RoomStatus::Available, $room->fresh()->status);
    }

    public function test_available_rooms_only_lists_rooms_with_free_beds(): void
    {
        $hostel = $this->makeHostel();
        $free = $this->makeRoom($hostel, ['room_number' => 'A1']);
        $full = $this->makeRoom($hostel, ['room_number' => 'A2', 'capacity' => 1]);

        $this->allocate($this->makeStudent(), $full);

        $student = $this->makeStudent();

        $response = $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/bookings/available-rooms')
            ->assertOk();

        $ids = collect($response->json('rooms'))->pluck('id');

        $this->assertTrue($ids->contains($free->id));
        $this->assertFalse($ids->contains($full->id));

        $freePayload = collect($response->json('rooms'))->firstWhere('id', $free->id);
        $this->assertSame($free->capacity, $freePayload['available_beds']);
    }
}
