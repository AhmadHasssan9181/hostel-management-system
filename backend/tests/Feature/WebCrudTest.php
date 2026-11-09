<?php

namespace Tests\Feature;

use App\Enums\FacilityStatus;
use App\Enums\InventoryCondition;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AppNotification;
use App\Models\Facility;
use App\Models\Hostel;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class WebCrudTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_staff_can_create_update_and_delete_a_hostel_block(): void
    {
        $employee = $this->makeUser(Role::Employee);

        $this->actingAs($employee)->post('/hostels', [
            'name' => 'New Block',
            'type' => 'girls',
            'location' => 'Campus C',
            'description' => 'Newly built block.',
        ])->assertRedirect();

        $hostel = Hostel::where('name', 'New Block')->firstOrFail();

        $this->actingAs($employee)->patch('/hostels/'.$hostel->id, [
            'location' => 'Campus D',
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertSame('Campus D', $hostel->fresh()->location);
        $this->assertFalse($hostel->fresh()->is_active);

        $this->actingAs($employee)->delete('/hostels/'.$hostel->id)->assertRedirect();
        $this->assertDatabaseMissing('hostels', ['id' => $hostel->id]);
    }

    public function test_a_block_with_residents_cannot_be_deleted(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();
        $hostel = $this->makeHostel();
        $this->allocate($student, $this->makeRoom($hostel));

        $this->actingAs($employee)
            ->delete('/hostels/'.$hostel->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('hostels', ['id' => $hostel->id]);
    }

    public function test_staff_can_create_update_and_delete_rooms(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $hostel = $this->makeHostel();

        $this->actingAs($employee)->post('/rooms', [
            'hostel_id' => $hostel->id,
            'room_number' => '900',
            'room_type' => 'deluxe',
            'capacity' => 3,
            'floor' => 9,
            'monthly_rent' => 9000,
        ])->assertRedirect();

        $room = Room::where('room_number', '900')->firstOrFail();

        $this->actingAs($employee)->patch('/rooms/'.$room->id, [
            'capacity' => 4,
            'monthly_rent' => 9500,
        ])->assertRedirect();

        $this->assertSame(4, $room->fresh()->capacity);

        $this->actingAs($employee)->delete('/rooms/'.$room->id)->assertRedirect();
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_room_photos_can_be_uploaded_and_resolved(): void
    {
        Storage::fake('public');

        $employee = $this->makeUser(Role::Employee);
        $hostel = $this->makeHostel();

        $this->actingAs($employee)->post('/rooms', [
            'hostel_id' => $hostel->id,
            'room_number' => '910',
            'capacity' => 2,
            'image' => UploadedFile::fake()->image('room.jpg'),
        ])->assertRedirect();

        $room = Room::where('room_number', '910')->firstOrFail();

        $this->assertNotNull($room->image_path);
        Storage::disk('public')->assertExists($room->image_path);
        $this->assertStringContainsString('/storage/', $room->image_url);
    }

    public function test_room_capacity_cannot_drop_below_occupied_beds(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $hostel = $this->makeHostel();
        $room = $this->makeRoom($hostel, ['capacity' => 2]);

        $this->allocate($this->makeStudent(), $room);
        $this->allocate($this->makeStudent(), $room->fresh());

        $this->actingAs($employee)
            ->patch('/rooms/'.$room->id, ['capacity' => 1])
            ->assertSessionHas('error');

        $this->assertSame(2, $room->fresh()->capacity);
    }

    public function test_facilities_crud_and_emergency_broadcast(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $admin = $this->makeUser(Role::Admin);
        $student = $this->makeStudent();

        $this->actingAs($employee)->post('/facilities', [
            'name' => 'Indoor Games',
            'type' => 'common',
            'location' => 'Block C',
            'capacity' => 25,
        ])->assertRedirect();

        $facility = Facility::where('name', 'Indoor Games')->firstOrFail();

        $this->actingAs($employee)
            ->patch('/facilities/'.$facility->id.'/status', ['status' => 'emergency'])
            ->assertRedirect();

        $this->assertSame(FacilityStatus::Emergency, $facility->fresh()->status);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $student->id,
            'type' => 'facility',
        ]);

        $this->actingAs($employee)->patch('/facilities/'.$facility->id, [
            'description' => 'Table tennis and carrom.',
            'is_visible' => '0',
        ])->assertRedirect();

        $this->assertFalse($facility->fresh()->is_visible);
        $this->assertSame('Table tennis and carrom.', $facility->fresh()->description);

        $this->actingAs($employee)->delete('/facilities/'.$facility->id)->assertForbidden();

        $this->actingAs($admin)->delete('/facilities/'.$facility->id)->assertRedirect();
        $this->assertDatabaseMissing('facilities', ['id' => $facility->id]);
    }

    public function test_inventory_can_be_stocked_adjusted_and_deleted(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $admin = $this->makeUser(Role::Admin);
        $hostel = $this->makeHostel();

        $this->actingAs($employee)->post('/inventory', [
            'name' => 'Plastic Chairs',
            'hostel_id' => $hostel->id,
            'category' => 'furniture',
            'quantity' => 10,
            'unit' => 'pcs',
            'threshold' => 4,
        ])->assertRedirect();

        $item = InventoryItem::where('name', 'Plastic Chairs')->firstOrFail();

        $this->actingAs($employee)
            ->post('/inventory/'.$item->id.'/adjust', ['delta' => 5, 'condition' => 'good'])
            ->assertRedirect();
        $this->assertSame(15, $item->fresh()->quantity);

        $this->actingAs($employee)
            ->post('/inventory/'.$item->id.'/adjust', ['delta' => -100])
            ->assertSessionHas('error');
        $this->assertSame(15, $item->fresh()->quantity);

        $this->actingAs($employee)->patch('/inventory/'.$item->id, [
            'condition' => 'damaged',
            'threshold' => 6,
        ])->assertRedirect();

        $this->assertSame(InventoryCondition::Damaged, $item->fresh()->condition);
        $this->assertTrue($item->fresh()->isLowStock() === false);

        $this->actingAs($admin)->delete('/inventory/'.$item->id)->assertRedirect();
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_admin_can_manage_user_accounts(): void
    {
        $admin = $this->makeUser(Role::Admin, ['email' => 'admin@hms.test']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'New Warden',
            'email' => 'neww@hms.test',
            'password' => 'Password@123',
            'role' => 'employee',
            'phone' => '0300-1112223',
        ])->assertRedirect();

        $user = User::where('email', 'neww@hms.test')->firstOrFail();

        $this->actingAs($admin)->patch('/users/'.$user->id, [
            'name' => 'Senior Warden',
            'status' => 'suspended',
        ])->assertRedirect();

        $this->assertSame('Senior Warden', $user->fresh()->name);
        $this->assertSame(UserStatus::Suspended, $user->fresh()->status);

        $this->actingAs($admin)->delete('/users/'.$user->id)->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_provision_a_student_account(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Provisioned Student',
            'email' => 'provisioned@hms.test',
            'password' => 'Password@123',
            'role' => 'student',
            'registration_number' => 'REG-PROV-001',
            'department' => 'Computer Science',
            'semester' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'provisioned@hms.test', 'role' => 'student']);
        $this->assertDatabaseHas('student_profiles', ['registration_number' => 'REG-PROV-001']);
    }

    public function test_employee_cannot_manage_users_or_delete_self(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($employee)->post('/users', [])->assertForbidden();

        $this->actingAs($admin)
            ->delete('/users/'.$admin->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_staff_can_update_a_student_record(): void
    {
        $employee = $this->makeUser(Role::Employee);
        $student = $this->makeStudent();

        $this->actingAs($employee)->patch('/students/'.$student->id, [
            'semester' => 7,
            'check_in_status' => 'checked_in',
            'discipline_status' => 'warning',
        ])->assertRedirect();

        $this->assertSame(7, $student->fresh()->studentProfile->semester);
        $this->assertSame('checked_in', $student->fresh()->studentProfile->check_in_status);
        $this->assertSame('warning', $student->fresh()->studentProfile->discipline_status);
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student)->patch('/profile', [
            'name' => 'Updated Name',
            'phone' => '0321-9998887',
            'address' => 'Islamabad',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '0333-1112223',
        ])->assertRedirect();

        $this->assertSame('Updated Name', $student->fresh()->name);
        $this->assertSame('Islamabad', $student->fresh()->studentProfile->address);

        $this->actingAs($student)->patch('/profile/password', [
            'current_password' => 'Password@123',
            'password' => 'BrandNew@123',
            'password_confirmation' => 'BrandNew@123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('BrandNew@123', $student->fresh()->password));

        $this->actingAs($student)->patch('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'Another@123',
            'password_confirmation' => 'Another@123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_notifications_can_be_read_and_deleted(): void
    {
        $student = $this->makeStudent();
        AppNotification::send($student, 'info', 'First');
        AppNotification::send($student, 'info', 'Second');

        $first = AppNotification::where('title', 'First')->firstOrFail();

        $this->actingAs($student)->post('/notifications/'.$first->id.'/read')->assertRedirect();
        $this->assertNotNull($first->fresh()->read_at);

        $this->actingAs($student)->post('/notifications/read-all')->assertRedirect();
        $this->assertSame(0, AppNotification::where('user_id', $student->id)->whereNull('read_at')->count());

        $this->actingAs($student)->delete('/notifications/'.$first->id)->assertRedirect();
        $this->assertDatabaseMissing('app_notifications', ['id' => $first->id]);
    }

    public function test_a_student_cannot_delete_another_students_notification(): void
    {
        $student = $this->makeStudent();
        $other = $this->makeStudent();
        AppNotification::send($student, 'info', 'Private');

        $notification = AppNotification::where('title', 'Private')->firstOrFail();

        $this->actingAs($other)
            ->delete('/notifications/'.$notification->id)
            ->assertForbidden();

        $this->assertDatabaseHas('app_notifications', ['id' => $notification->id]);
    }

    public function test_logout_ends_the_session(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
