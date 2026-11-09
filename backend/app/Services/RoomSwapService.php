<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\RequestStatus;
use App\Enums\RoomStatus;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomSwapRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomSwapService
{
    public function request(User $student, array $data): RoomSwapRequest
    {
        $active = Booking::query()
            ->where('student_id', $student->id)
            ->where('status', BookingStatus::Approved->value)
            ->first();

        if (! $active) {
            throw ValidationException::withMessages([
                'current_room_id' => 'You need an approved room allocation before requesting a swap.',
            ]);
        }

        $duplicate = RoomSwapRequest::query()
            ->where('student_id', $student->id)
            ->where('status', RequestStatus::Pending->value)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'reason' => 'You already have a pending room swap request.',
            ]);
        }

        $target = Room::query()->findOrFail($data['requested_room_id']);

        if ($target->id === $active->room_id) {
            throw ValidationException::withMessages([
                'requested_room_id' => 'You already live in this room.',
            ]);
        }

        return RoomSwapRequest::create([
            'student_id' => $student->id,
            'current_room_id' => $active->room_id,
            'requested_room_id' => $target->id,
            'reason' => $data['reason'],
            'status' => RequestStatus::Pending,
        ]);
    }

    public function approve(RoomSwapRequest $swap, User $reviewer, ?string $note = null): RoomSwapRequest
    {
        return DB::transaction(function () use ($swap, $reviewer, $note) {
            /** @var RoomSwapRequest $swap */
            $swap = RoomSwapRequest::query()->lockForUpdate()->findOrFail($swap->id);

            if ($swap->status !== RequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending swap requests can be approved.',
                ]);
            }

            $booking = Booking::query()
                ->where('student_id', $swap->student_id)
                ->where('status', BookingStatus::Approved->value)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                throw ValidationException::withMessages([
                    'student_id' => 'The student has no active allocation to swap.',
                ]);
            }

            /** @var Room $oldRoom */
            $oldRoom = Room::query()->lockForUpdate()->findOrFail($booking->room_id);
            /** @var Room $newRoom */
            $newRoom = Room::query()->lockForUpdate()->findOrFail($swap->requested_room_id);

            if ($newRoom->status === RoomStatus::Maintenance || $newRoom->occupied_beds >= $newRoom->capacity) {
                throw ValidationException::withMessages([
                    'requested_room_id' => 'The requested room is no longer available.',
                ]);
            }

            $oldRoom->decrement('occupied_beds');
            $oldRoom->refresh()->syncStatus();

            $newRoom->increment('occupied_beds');
            $newRoom->refresh()->syncStatus();

            $booking->update(['room_id' => $newRoom->id]);

            $swap->update([
                'status' => RequestStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            AppNotification::send(
                $swap->student,
                'room_swap',
                'Room swap approved',
                "You have moved from room {$oldRoom->room_number} to room {$newRoom->room_number}.",
                ['swap_request_id' => $swap->id],
            );

            return $swap->fresh(['currentRoom.hostel', 'requestedRoom.hostel']);
        });
    }

    public function reject(RoomSwapRequest $swap, User $reviewer, ?string $note = null): RoomSwapRequest
    {
        if ($swap->status !== RequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending swap requests can be rejected.',
            ]);
        }

        $swap->update([
            'status' => RequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        AppNotification::send(
            $swap->student,
            'room_swap',
            'Room swap rejected',
            $note ?: 'Your room swap request was rejected.',
            ['swap_request_id' => $swap->id],
        );

        return $swap;
    }

    public function cancel(RoomSwapRequest $swap): RoomSwapRequest
    {
        if ($swap->status !== RequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending swap requests can be cancelled.',
            ]);
        }

        $swap->update(['status' => RequestStatus::Cancelled, 'reviewed_at' => now()]);

        return $swap;
    }
}
