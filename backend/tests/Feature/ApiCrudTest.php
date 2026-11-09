<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AppNotification;
use App\Models\Hostel;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Models\StudentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class ApiCrudTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_the_api_health_endpoint_responds(): void
    {
        $this->getJson('/api/v1')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_hostels_and_rooms_can_be_managed_through_the_api(): void
    {
        $employee = $this->makeUser(Role::Employee);

        $hostelResponse = $this->actingAs($employee, 'sanctum')->postJson('/api/v1/hostels', [
            'name' => 'API Block',
            'type' => 'boys',
            'location' => 'Campus',
        ])->assertCreated();

        $hostelId = $hostelResponse->json('hostel.id');

        $this->actingAs($employee, 'sanctum')->patchJson('/api/v1/hostels/'.$hostelId, [
            'location' => 'Campus East',
        ])->assertOk();

        $this->assertSame('Campus East', Hostel::find($hostelId)->location);

        $roomResponse = $this->actingAs($employee, 'sanctum')->postJson('/api/v1/rooms', [
            'hostel_id' => $hostelId,
            'room_number' => 'A-1',
            'capacity' => 2,
            'monthly_rent' => 6000,
        ])->assertCreated();

        $roomId = $roomResponse->json('room.id');

        $this->actingAs($employee, 'sanctum')
            ->patchJson('/api/v1/rooms/'.$roomId, ['monthly_rent' => 7000])
            ->assertOk();

        $this->assertSame('7000.00', Room::find($roomId)->monthly_rent);

        $this->actingAs($employee, 'sanctum')->deleteJson('/api/v1/rooms/'.$roomId)->assertOk();
        $this->actingAs($employee, 'sanctum')->deleteJson('/api/v1/hostels/'.$hostelId)->assertOk();

        $this->assertDatabaseMissing('rooms', ['id' => $roomId]);
        $this->assertDatabaseMissing('hostels', ['id' => $hostelId]);
    }

    public function test_students_are_blocked_from_hostel_and_room_management(): void
    {
        $student = $this->makeStudent();
        $hostel = $this->makeHostel();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/hostels', ['name' => 'Nope', 'type' => 'boys'])
            ->assertForbidden();

        $this->actingAs($student, 'sanctum')
            ->deleteJson('/api/v1/rooms/'.$this->makeRoom($hostel)->id)
            ->assertForbidden();
    }

    public function test_facilities_and_inventory_are_managed_through_the_api(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $admin = $this->makeUser(Role::Admin);
        $student = $this->makeStudent();

        $facilityResponse = $this->actingAs($employee, 'sanctum')->postJson('/api/v1/facilities', [
            'name' => 'API Hall',
            'type' => 'study',
            'capacity' => 20,
        ])->assertCreated();

        $facilityId = $facilityResponse->json('facility.id');

        $this->actingAs($employee, 'sanctum')
            ->patchJson('/api/v1/facilities/'.$facilityId.'/status', ['status' => 'emergency'])
            ->assertOk();

        $this->assertDatabaseHas('app_notifications', ['user_id' => $student->id, 'type' => 'facility']);

        $this->actingAs($employee, 'sanctum')
            ->deleteJson('/api/v1/facilities/'.$facilityId)
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/facilities/'.$facilityId)
            ->assertOk();

        $itemResponse = $this->actingAs($employee, 'sanctum')->postJson('/api/v1/inventory', [
            'name' => 'API Beds',
            'quantity' => 4,
            'threshold' => 2,
        ])->assertCreated();

        $itemId = $itemResponse->json('item.id');

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/inventory/'.$itemId.'/adjust', ['delta' => 3])
            ->assertOk();

        $this->assertSame(7, InventoryItem::find($itemId)->quantity);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/inventory/'.$itemId)
            ->assertOk();

        $this->assertDatabaseMissing('inventory_items', ['id' => $itemId]);
    }

    public function test_staff_can_read_and_update_student_records(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();

        $this->actingAs($employee, 'sanctum')
            ->getJson('/api/v1/students')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->actingAs($employee, 'sanctum')
            ->getJson('/api/v1/students/'.$student->id)
            ->assertOk();

        $this->actingAs($employee, 'sanctum')
            ->patchJson('/api/v1/students/'.$student->id, ['semester' => 8])
            ->assertOk();

        $this->assertSame(8, $student->fresh()->studentProfile->semester);
    }

    public function test_admin_can_manage_users_through_the_api(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $employee = $this->makeUser(Role::Employee);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', [
            'name' => 'API Clerk',
            'email' => 'apiclerk@hms.test',
            'password' => 'Password@123',
            'role' => 'employee',
        ])->assertCreated();

        $userId = $response->json('user.id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/users/'.$userId, ['status' => 'suspended'])
            ->assertOk();

        $this->assertSame('suspended', User::find($userId)->status->value);

        $this->actingAs($employee, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/users/'.$userId)
            ->assertOk();
    }

    public function test_student_requests_and_notifications_work_through_the_api(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);

        $response = $this->actingAs($student, 'sanctum')->postJson('/api/v1/student-requests', [
            'type' => 'leave',
            'subject' => 'API leave',
            'details' => 'Family visit.',
        ])->assertCreated();

        $requestId = $response->json('request.id');

        $this->actingAs($employee, 'sanctum')->postJson('/api/v1/student-requests/'.$requestId.'/review', [
            'status' => 'approved',
        ])->assertOk();

        $this->assertSame('approved', StudentRequest::find($requestId)->status->value);

        AppNotification::send($student, 'info', 'API notification');

        $notification = AppNotification::where('title', 'API notification')->firstOrFail();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/notifications/'.$notification->id.'/read')
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($student, 'sanctum')
            ->deleteJson('/api/v1/notifications/'.$notification->id)
            ->assertOk();
    }

    public function test_online_room_availability_exposes_computed_free_beds(): void
    {
        $student = $this->makeStudent();
        $room = $this->makeRoom($this->makeHostel(), ['capacity' => 2]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/bookings/available-rooms')
            ->assertOk();

        $payload = collect($response->json('rooms'))->firstWhere('id', $room->id);

        $this->assertNotNull($payload);
        $this->assertSame(2, $payload['available_beds']);
    }
}
