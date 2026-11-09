<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * A student may hold at most one active request at a time.
     */
    public function request(User $student, array $data): Booking
    {
        return DB::transaction(function () use ($student, $data) {
            $alreadyActive = Booking::query()
                ->where('student_id', $student->id)
                ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Approved->value])
                ->exists();

            if ($alreadyActive) {
                throw ValidationException::withMessages([
                    'room_id' => 'You already have an active booking or a pending request.',
                ]);
            }

            /** @var Room $room */
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);

            if ($room->status === RoomStatus::Maintenance) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room is under maintenance.',
                ]);
            }

            if ($room->occupied_beds >= $room->capacity) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room is full. Please choose another room.',
                ]);
            }

            return Booking::create([
                'student_id' => $student->id,
                'room_id' => $room->id,
                'status' => BookingStatus::Pending,
                'requested_from' => $data['requested_from'] ?? now()->toDateString(),
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    public function approve(Booking $booking, User $reviewer, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($booking, $reviewer, $note) {
            /** @var Booking $booking */
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending bookings can be approved.',
                ]);
            }

            /** @var Room $room */
            $room = Room::query()->lockForUpdate()->findOrFail($booking->room_id);

            if ($room->status === RoomStatus::Maintenance || $room->occupied_beds >= $room->capacity) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room is no longer available. Reject the request or pick another room.',
                ]);
            }

            $room->increment('occupied_beds');
            $room->refresh()->syncStatus();

            $booking->update([
                'status' => BookingStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            Booking::query()
                ->where('student_id', $booking->student_id)
                ->whereKeyNot($booking->id)
                ->where('status', BookingStatus::Pending->value)
                ->update([
                    'status' => BookingStatus::Rejected->value,
                    'review_note' => 'Auto-closed: another room was allocated.',
                    'reviewed_at' => now(),
                ]);

            AppNotification::send(
                $booking->student,
                'booking',
                'Room allocation approved',
                "Room {$room->room_number} has been allocated to you.",
                ['booking_id' => $booking->id, 'room_id' => $room->id],
            );

            return $booking->fresh(['room.hostel', 'student.studentProfile']);
        });
    }

    public function reject(Booking $booking, User $reviewer, ?string $note = null): Booking
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending bookings can be rejected.',
            ]);
        }

        $booking->update([
            'status' => BookingStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        AppNotification::send(
            $booking->student,
            'booking',
            'Room request rejected',
            $note ?: 'Your room request was rejected.',
            ['booking_id' => $booking->id],
        );

        return $booking->fresh('room.hostel');
    }

    /**
     * Update a booking's room and/or note. Moving an approved booking keeps
     * both rooms' bed counts in sync.
     */
    public function update(Booking $booking, array $data, User $staff): Booking
    {
        return DB::transaction(function () use ($booking, $data, $staff) {
            /** @var Booking $booking */
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (in_array($booking->status, [
                BookingStatus::Rejected,
                BookingStatus::Cancelled,
                BookingStatus::CheckedOut,
            ], true)) {
                throw ValidationException::withMessages([
                    'room_id' => 'This booking can no longer be edited.',
                ]);
            }

            $newRoomId = $data['room_id'] ?? $booking->room_id;

            if ((int) $newRoomId !== (int) $booking->room_id) {
                /** @var Room $oldRoom */
                $oldRoom = Room::query()->lockForUpdate()->findOrFail($booking->room_id);
                /** @var Room $newRoom */
                $newRoom = Room::query()->lockForUpdate()->findOrFail($newRoomId);

                if ($newRoom->status === RoomStatus::Maintenance || $newRoom->occupied_beds >= $newRoom->capacity) {
                    throw ValidationException::withMessages([
                        'room_id' => 'The selected room is not available.',
                    ]);
                }

                if ($booking->status === BookingStatus::Approved) {
                    $oldRoom->decrement('occupied_beds');
                    $oldRoom->refresh()->syncStatus();

                    $newRoom->increment('occupied_beds');
                    $newRoom->refresh()->syncStatus();
                }

                $booking->room_id = $newRoom->id;
            }

            if (array_key_exists('note', $data)) {
                $booking->note = $data['note'];
            }

            $booking->reviewed_by = $staff->id;
            $booking->reviewed_at = now();
            $booking->save();

            return $booking->fresh(['room.hostel', 'student.studentProfile']);
        });
    }

    public function cancel(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending bookings can be cancelled.',
            ]);
        }

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'reviewed_at' => now(),
        ]);

        return $booking;
    }

    /**
     * Check a resident out: free the bed and record the departure.
     */
    public function checkOut(Booking $booking, User $reviewer): Booking
    {
        return DB::transaction(function () use ($booking, $reviewer) {
            /** @var Booking $booking */
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => 'Only an approved booking can be checked out.',
                ]);
            }

            $room = Room::query()->lockForUpdate()->findOrFail($booking->room_id);
            $room->decrement('occupied_beds');
            $room->refresh()->syncStatus();

            $booking->update([
                'status' => BookingStatus::CheckedOut,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            AppNotification::send(
                $booking->student,
                'booking',
                'Checked out',
                "You have been checked out of room {$room->room_number}.",
                ['booking_id' => $booking->id],
            );

            return $booking->fresh('room.hostel');
        });
    }
}
