<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\RoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class RoomSwapTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_a_student_can_request_a_swap_and_staff_can_approve_it(): void
    {
        $hostel = $this->makeHostel();
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);

        $current = $this->makeRoom($hostel, ['room_number' => 'C1', 'capacity' => 2]);
        $target = $this->makeRoom($hostel, ['room_number' => 'C2', 'capacity' => 2]);

        $this->allocate($student, $current);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/room-swap-requests', [
                'requested_room_id' => $target->id,
                'reason' => 'I need a ground floor room because of a knee injury.',
            ]);

        $response->assertCreated()->assertJsonPath('swap_request.status', 'pending');

        $swapId = $response->json('swap_request.id');

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/room-swap-requests/{$swapId}/approve")
            ->assertOk()
            ->assertJsonPath('swap_request.status', 'approved');

        $this->assertSame(0, $current->fresh()->occupied_beds);
        $this->assertSame(1, $target->fresh()->occupied_beds);
        $this->assertSame(RoomStatus::Available, $current->fresh()->status);
        $this->assertSame($target->id, $student->activeBooking->room_id);
    }

    public function test_a_student_without_an_allocation_cannot_request_a_swap(): void
    {
        $student = $this->makeStudent();
        $room = $this->makeRoom($this->makeHostel());

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/room-swap-requests', [
                'requested_room_id' => $room->id,
                'reason' => 'I would like a room closer to the mess hall.',
            ])
            ->assertUnprocessable();
    }

    public function test_a_swap_cannot_be_approved_into_a_full_room(): void
    {
        $hostel = $this->makeHostel();
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);

        $current = $this->makeRoom($hostel, ['room_number' => 'D1', 'capacity' => 2]);
        $target = $this->makeRoom($hostel, ['room_number' => 'D2', 'capacity' => 1]);

        $this->allocate($student, $current);
        $this->allocate($this->makeStudent(), $target);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/room-swap-requests', [
                'requested_room_id' => $target->id,
                'reason' => 'I want to move to the quieter side of the block.',
            ])->assertCreated();

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/room-swap-requests/'.$response->json('swap_request.id').'/approve')
            ->assertUnprocessable();
    }
}
