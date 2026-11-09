<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\FacilityStatus;
use App\Enums\FeeStatus;
use App\Enums\InventoryCondition;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Enums\StudentRequestType;
use App\Enums\UserStatus;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Facility;
use App\Models\Fee;
use App\Models\Hostel;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Models\RoomSwapRequest;
use App\Models\StudentProfile;
use App\Models\StudentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->user('System Admin', 'admin@hms.test', Role::Admin, 'Admin@1234', '0300-0000001');
        $warden = $this->user('Shamsa Bano', 'warden@hms.test', Role::Employee, 'Staff@1234', '0300-0000002');
        $clerk = $this->user('Hostel Clerk', 'clerk@hms.test', Role::Employee, 'Staff@1234', '0300-0000003');

        $boys = Hostel::create([
            'name' => 'Iqbal Hostel (Boys)',
            'type' => 'boys',
            'location' => 'FUUAST Islamabad, Block A',
            'total_rooms' => 24,
            'description' => 'Main boys hostel with mess and gym access.',
            'image_path' => 'images/hostels/iqbal.jpg',
            'images' => ['images/rooms/standard.jpg', 'images/facilities/gym.jpg'],
            'is_active' => true,
        ]);

        $girls = Hostel::create([
            'name' => 'Fatima Hostel (Girls)',
            'type' => 'girls',
            'location' => 'FUUAST Islamabad, Block B',
            'total_rooms' => 18,
            'description' => 'Girls hostel with laundry and study hall.',
            'image_path' => 'images/hostels/fatima.jpg',
            'images' => ['images/rooms/deluxe.jpg', 'images/facilities/study.jpg'],
            'is_active' => true,
        ]);

        $rooms = collect();

        $roomImages = [
            // Bright, neutral hotel bedrooms
            ['images/rooms/unique/room-01.jpg', 'images/rooms/unique/room-03.jpg', 'images/rooms/unique/room-04.jpg'],
            // Dark, moody bedrooms
            ['images/rooms/unique/room-07.jpg', 'images/rooms/unique/room-06.jpg', 'images/rooms/unique/room-09.jpg'],
            // Green / teal bedrooms
            ['images/rooms/unique/room-02.jpg', 'images/rooms/unique/room-05.jpg', 'images/rooms/unique/room-08.jpg'],
            // Classic / warm bedrooms
            ['images/rooms/unique/room-10.jpg', 'images/rooms/unique/room-11.jpg', 'images/rooms/unique/room-12.jpg'],
        ];
        $roomImageIndex = 0;

        foreach ([[$boys, 4, 6], [$girls, 3, 6]] as [$hostel, $floors, $perFloor]) {
            for ($floor = 1; $floor <= $floors; $floor++) {
                for ($i = 1; $i <= $perFloor; $i++) {
                    $type = $i % 3 === 0 ? 'deluxe' : 'standard';
                    $set = $roomImages[$roomImageIndex % count($roomImages)];
                    $shift = intdiv($roomImageIndex, count($roomImages)) % count($set);
                    $primaryImage = $set[$shift];
                    $secondaryImage = $set[($shift + 1) % count($set)];
                    $thirdImage = $set[($shift + 2) % count($set)];
                    $roomImageIndex++;

                    $rooms->push(Room::create([
                        'hostel_id' => $hostel->id,
                        'room_number' => $floor.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                        'room_type' => $type,
                        'image_path' => $primaryImage,
                        'images' => [$secondaryImage, $thirdImage],
                        'capacity' => $i % 3 === 0 ? 3 : 2,
                        'occupied_beds' => 0,
                        'floor' => $floor,
                        'monthly_rent' => $i % 3 === 0 ? 8500 : 6500,
                    ]));
                }
            }
        }

        $students = collect();

        $names = [
            'Insha Murtaza', 'Ayesha Khan', 'Hamza Ali', 'Fatima Noor', 'Bilal Ahmed',
            'Zainab Raza', 'Usman Tariq', 'Maryam Shah', 'Ali Hassan', 'Sana Iqbal',
        ];

        foreach ($names as $index => $name) {
            $student = $this->user(
                $name,
                'student'.($index + 1).'@hms.test',
                Role::Student,
                'Student@123',
                '0311-00000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            );

            StudentProfile::create([
                'user_id' => $student->id,
                'registration_number' => 'BS CS SP-2023-Eve-'.str_pad((string) (212 + $index), 4, '0', STR_PAD_LEFT),
                'department' => 'Computer Science',
                'semester' => 3 + ($index % 4),
                'address' => 'Rawalpindi, Punjab',
                'guardian_name' => 'Guardian of '.$name,
                'guardian_phone' => '0333-00000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'check_in_status' => $index < 6 ? 'checked_in' : 'pending',
            ]);

            $students->push($student);
        }

        $femaleNames = ['Insha Murtaza', 'Ayesha Khan', 'Fatima Noor', 'Zainab Raza', 'Maryam Shah', 'Sana Iqbal'];
        $femaleIndex = 0;
        $maleIndex = 0;

        $girlsRooms = $rooms->where('hostel_id', $girls->id)->values();
        $boysRooms = $rooms->where('hostel_id', $boys->id)->values();

        $boysRoom = fn (string $number) => $rooms->first(
            fn (Room $room) => $room->hostel_id === $boys->id && $room->room_number === $number
        );
        $girlsRoom = fn (string $number) => $rooms->first(
            fn (Room $room) => $room->hostel_id === $girls->id && $room->room_number === $number
        );

        foreach ($students->take(6) as $student) {
            $isFemale = in_array($student->name, $femaleNames, true);
            $room = $isFemale ? $girlsRooms[$femaleIndex++] : $boysRooms[$maleIndex++];

            Booking::create([
                'student_id' => $student->id,
                'room_id' => $room->id,
                'reviewed_by' => $warden->id,
                'status' => BookingStatus::Approved,
                'requested_from' => now()->subMonths(2)->toDateString(),
                'review_note' => 'Allocated at the start of the semester.',
                'reviewed_at' => now()->subMonths(2),
            ]);

            $room->increment('occupied_beds');
            $room->refresh()->syncStatus();
        }

        Booking::create([
            'student_id' => $students[6]->id,
            'room_id' => $boysRoom('105')->id,
            'status' => BookingStatus::Pending,
            'requested_from' => now()->toDateString(),
            'note' => 'Prefer a room close to the mess.',
        ]);

        Booking::create([
            'student_id' => $students[7]->id,
            'room_id' => $girlsRoom('203')->id,
            'status' => BookingStatus::Pending,
            'requested_from' => now()->toDateString(),
        ]);

        $complaintSeed = [
            ['wifi', ComplaintPriority::High, ComplaintStatus::Open, 'Wi-Fi keeps disconnecting on the second floor since yesterday.'],
            ['plumbing', ComplaintPriority::Urgent, ComplaintStatus::InProgress, 'Water is leaking from the bathroom tap and flooding the floor.'],
            ['electrical', ComplaintPriority::Normal, ComplaintStatus::Resolved, 'The ceiling fan in my room makes a loud noise at high speed.'],
            ['cleanliness', ComplaintPriority::Low, ComplaintStatus::Open, 'The corridor bins have not been emptied for two days.'],
        ];

        foreach ($complaintSeed as $index => [$category, $priority, $status, $description]) {
            Complaint::create([
                'ticket_no' => 'HMS-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'student_id' => $students[$index]->id,
                'room_id' => $students[$index]->activeBooking?->room_id,
                'category' => $category,
                'priority' => $priority,
                'description' => $description,
                'status' => $status,
                'assigned_to' => in_array($status, [ComplaintStatus::InProgress, ComplaintStatus::Resolved], true) ? $clerk->id : null,
                'resolution_note' => $status === ComplaintStatus::Resolved ? 'Fan bearings replaced.' : null,
                'resolved_at' => $status === ComplaintStatus::Resolved ? now()->subDay() : null,
            ]);
        }

        $feeStatuses = [FeeStatus::Paid, FeeStatus::Unpaid, FeeStatus::Overdue, FeeStatus::PendingVerification, FeeStatus::Paid, FeeStatus::Unpaid];

        foreach ($students->take(6) as $index => $student) {
            $status = $feeStatuses[$index];

            Fee::create([
                'reference' => 'FEE-'.now()->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'student_id' => $student->id,
                'booking_id' => $student->activeBooking?->id,
                'type' => 'hostel_fee',
                'period' => now()->format('Y-m'),
                'amount' => 6500,
                'fine' => $status === FeeStatus::Overdue ? 500 : 0,
                'status' => $status,
                'due_date' => now()->addDays(10)->toDateString(),
                'paid_at' => $status === FeeStatus::Paid ? now()->subDays(3) : null,
                'payment_method' => 'bank_transfer',
                'verified_by' => $status === FeeStatus::Paid ? $clerk->id : null,
            ]);
        }

        $facilities = [
            ['Mess Hall', 'mess', 'Daily breakfast, lunch and dinner service.', 'Block A, Ground Floor', '07:00', '21:00', 200, FacilityStatus::Operational, 'images/facilities/mess.jpg'],
            ['Gym', 'gym', 'Cardio and weights area for residents.', 'Block A, Basement', '06:00', '22:00', 30, FacilityStatus::Operational, 'images/facilities/gym.jpg'],
            ['Laundry Room', 'laundry', 'Washing machines and ironing stations.', 'Block B, Ground Floor', '08:00', '20:00', 12, FacilityStatus::Maintenance, 'images/facilities/laundry.jpg'],
            ['Study Hall', 'study', 'Quiet study space with Wi-Fi.', 'Block B, First Floor', '00:00', '23:59', 60, FacilityStatus::Operational, 'images/facilities/study.jpg'],
            ['Common Room', 'common', 'TV, indoor games and seating.', 'Block A, First Floor', '09:00', '23:00', 40, FacilityStatus::Operational, 'images/facilities/common.jpg'],
        ];

        foreach ($facilities as [$name, $type, $description, $location, $opens, $closes, $capacity, $status, $image]) {
            Facility::create([
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'location' => $location,
                'image_path' => $image,
                'opens_at' => $opens === '00:00' ? null : $opens,
                'closes_at' => $closes === '23:59' ? null : $closes,
                'capacity' => $capacity,
                'status' => $status,
                'is_visible' => true,
            ]);
        }

        $inventory = [
            ['Steel Beds', 'furniture', 48, 'pcs', 10, InventoryCondition::Good],
            ['Study Tables', 'furniture', 40, 'pcs', 8, InventoryCondition::Good],
            ['Ceiling Fans', 'appliance', 35, 'pcs', 5, InventoryCondition::Good],
            ['Water Coolers', 'appliance', 4, 'pcs', 5, InventoryCondition::Damaged],
            ['Mattresses', 'furniture', 9, 'pcs', 12, InventoryCondition::Good],
            ['Mess Chairs', 'mess', 60, 'pcs', 15, InventoryCondition::Good],
            ['Cooking Gas Cylinders', 'mess', 2, 'cylinders', 4, InventoryCondition::Damaged],
            ['First Aid Kits', 'safety', 3, 'kits', 6, InventoryCondition::Good],
        ];

        foreach ($inventory as [$name, $category, $quantity, $unit, $threshold, $condition]) {
            InventoryItem::create([
                'hostel_id' => $boys->id,
                'name' => $name,
                'category' => $category,
                'quantity' => $quantity,
                'unit' => $unit,
                'threshold' => $threshold,
                'condition' => $condition,
            ]);
        }

        StudentRequest::create([
            'student_id' => $students[7]->id,
            'type' => StudentRequestType::Leave,
            'subject' => 'Weekend leave to visit family',
            'details' => 'Leaving Friday evening and returning Sunday night.',
            'from_date' => now()->next('Friday')->toDateString(),
            'to_date' => now()->next('Sunday')->toDateString(),
            'status' => RequestStatus::Pending,
        ]);

        StudentRequest::create([
            'student_id' => $students[8]->id,
            'type' => StudentRequestType::Leave,
            'subject' => 'Medical appointment',
            'details' => 'Follow-up appointment at the hospital.',
            'from_date' => now()->addDays(3)->toDateString(),
            'to_date' => now()->addDays(3)->toDateString(),
            'status' => RequestStatus::Approved,
            'reviewed_by' => $warden->id,
            'review_note' => 'Approved, please return by 9 PM.',
            'reviewed_at' => now()->subDay(),
        ]);

        $swapStudent = $students[5];
        $swapTarget = $girlsRoom('204');

        RoomSwapRequest::create([
            'student_id' => $swapStudent->id,
            'current_room_id' => $swapStudent->activeBooking?->room_id,
            'requested_room_id' => $swapTarget->id,
            'reason' => 'My current room is on the top floor and I have a knee injury.',
            'status' => RequestStatus::Pending,
        ]);

        AppNotification::send($students[0], 'info', 'Welcome to HMS', 'Your hostel account is ready.');
        AppNotification::send($students[0], 'fee', 'Fee reminder', 'Your hostel fee for this month is due in 10 days.');
        AppNotification::send($warden, 'complaint', 'Urgent complaint pending', 'A plumbing complaint is marked urgent.');

        $this->command?->info('Demo data ready.');
        $this->command?->info('admin@hms.test / Admin@1234');
        $this->command?->info('warden@hms.test / Staff@1234');
        $this->command?->info('student1@hms.test / Student@123');
    }

    private function user(string $name, string $email, Role $role, string $password, ?string $phone = null): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'phone' => $phone,
            'status' => UserStatus::Active,
        ]);
    }
}
