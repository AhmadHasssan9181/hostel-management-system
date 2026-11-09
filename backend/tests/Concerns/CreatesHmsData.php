<?php

namespace Tests\Concerns;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Booking;
use App\Models\Hostel;
use App\Models\Room;
use App\Models\StudentProfile;
use App\Models\User;

trait CreatesHmsData
{
    protected static int $roomCounter = 100;

    protected function makeUser(Role $role = Role::Student, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => $role->value.' user',
            'email' => $role->value.'-'.uniqid().'@hms.test',
            'password' => 'Password@123',
            'role' => $role,
            'status' => UserStatus::Active,
        ], $overrides));
    }

    protected function makeStudent(array $overrides = []): User
    {
        $student = $this->makeUser(Role::Student, $overrides);

        StudentProfile::create([
            'user_id' => $student->id,
            'registration_number' => 'REG-'.uniqid(),
            'department' => 'Computer Science',
            'semester' => 4,
        ]);

        return $student->fresh('studentProfile');
    }

    protected function makeHostel(array $overrides = []): Hostel
    {
        return Hostel::create(array_merge([
            'name' => 'Test Hostel '.uniqid(),
            'type' => 'boys',
            'location' => 'Campus',
            'total_rooms' => 10,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeRoom(Hostel $hostel, array $overrides = []): Room
    {
        return Room::create(array_merge([
            'hostel_id' => $hostel->id,
            'room_number' => 'R'.(self::$roomCounter++),
            'room_type' => 'standard',
            'capacity' => 2,
            'occupied_beds' => 0,
            'floor' => 1,
            'monthly_rent' => 6500,
        ], $overrides));
    }

    protected function allocate(User $student, Room $room): Booking
    {
        $booking = Booking::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'status' => BookingStatus::Approved,
            'requested_from' => now()->toDateString(),
            'reviewed_at' => now(),
        ]);

        $room->increment('occupied_beds');
        $room->refresh()->syncStatus();

        return $booking;
    }
}
