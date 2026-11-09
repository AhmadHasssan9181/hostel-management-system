<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ComplaintService
{
    public function create(User $student, array $data, ?UploadedFile $photo = null): Complaint
    {
        $active = Booking::query()
            ->where('student_id', $student->id)
            ->where('status', BookingStatus::Approved->value)
            ->first();

        if (! $active) {
            throw ValidationException::withMessages([
                'category' => 'Only residents with an approved room can file a complaint.',
            ]);
        }

        $path = $photo?->store('complaints', 'public');

        return Complaint::create([
            'student_id' => $student->id,
            'room_id' => $active->room_id,
            'category' => $data['category'],
            'priority' => $data['priority'] ?? ComplaintPriority::Normal,
            'description' => $data['description'],
            'photo_path' => $path,
            'status' => ComplaintStatus::Open,
        ]);
    }

    public function assign(Complaint $complaint, User $staff): Complaint
    {
        if ($complaint->isResolved()) {
            throw ValidationException::withMessages([
                'status' => 'Resolved complaints cannot be reassigned.',
            ]);
        }

        $complaint->update([
            'assigned_to' => $staff->id,
            'status' => $complaint->status === ComplaintStatus::Open
                ? ComplaintStatus::InProgress
                : $complaint->status,
        ]);

        AppNotification::send(
            $complaint->student,
            'complaint',
            'Complaint assigned',
            "Ticket {$complaint->ticket_no} is now being handled by {$staff->name}.",
            ['complaint_id' => $complaint->id],
        );

        return $complaint->fresh(['assignee', 'room.hostel']);
    }

    public function updateStatus(Complaint $complaint, array $data, User $staff): Complaint
    {
        $status = ComplaintStatus::from($data['status']);

        if ($complaint->isResolved() && $status !== ComplaintStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'A resolved complaint can only be closed.',
            ]);
        }

        $payload = [
            'status' => $status,
            'resolution_note' => $data['resolution_note'] ?? $complaint->resolution_note,
        ];

        if ($status === ComplaintStatus::Resolved) {
            $payload['resolved_at'] = now();
        }

        if ($status === ComplaintStatus::Closed) {
            $payload['closed_at'] = now();
        }

        $complaint->update($payload);

        if (in_array($status, [ComplaintStatus::Resolved, ComplaintStatus::Closed], true)) {
            AppNotification::send(
                $complaint->student,
                'complaint',
                'Complaint '.$status->value,
                "Ticket {$complaint->ticket_no} has been marked as {$status->value}.",
                ['complaint_id' => $complaint->id],
            );
        }

        return $complaint->fresh(['assignee', 'room.hostel']);
    }

    /**
     * Escalate complaints that have sat unassigned past the SLA window.
     */
    public function escalateStale(int $hours = 48): int
    {
        $stale = Complaint::query()
            ->whereIn('status', [ComplaintStatus::Open->value, ComplaintStatus::InProgress->value])
            ->whereNull('assigned_to')
            ->where('created_at', '<=', now()->subHours($hours))
            ->get();

        $staff = User::query()
            ->whereIn('role', [Role::Employee->value, Role::Admin->value])
            ->where('status', UserStatus::Active->value)
            ->get();

        foreach ($stale as $complaint) {
            $complaint->update(['status' => ComplaintStatus::Escalated]);

            foreach ($staff as $member) {
                AppNotification::send(
                    $member,
                    'complaint_escalation',
                    'Complaint escalated',
                    "Ticket {$complaint->ticket_no} has been unassigned for over {$hours} hours.",
                    ['complaint_id' => $complaint->id],
                );
            }
        }

        return $stale->count();
    }

    public function deletePhoto(Complaint $complaint): void
    {
        if ($complaint->photo_path) {
            Storage::disk('public')->delete($complaint->photo_path);
        }
    }
}
