<?php

namespace App\Services;

use App\Enums\FeeStatus;
use App\Models\AppNotification;
use App\Models\Fee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeeService
{
    public function generate(User $student, array $data): Fee
    {
        return Fee::create([
            'reference' => 'FEE-'.now()->format('Ym').'-'.Str::upper(Str::random(6)),
            'student_id' => $student->id,
            'booking_id' => $data['booking_id'] ?? $student->activeBooking?->id,
            'type' => $data['type'] ?? 'hostel_fee',
            'period' => $data['period'] ?? now()->format('Y-m'),
            'amount' => $data['amount'],
            'fine' => $data['fine'] ?? 0,
            'status' => FeeStatus::Unpaid,
            'due_date' => $data['due_date'],
        ]);
    }

    public function submitPayment(Fee $fee, User $student, array $data, ?UploadedFile $receipt = null): Fee
    {
        if ($fee->student_id !== $student->id) {
            throw ValidationException::withMessages(['fee' => 'This invoice does not belong to you.']);
        }

        if (in_array($fee->status, [FeeStatus::Paid, FeeStatus::Waived], true)) {
            throw ValidationException::withMessages(['fee' => 'This invoice is already settled.']);
        }

        $path = $receipt?->store('receipts', 'public');

        $fee->update([
            'status' => FeeStatus::PendingVerification,
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'receipt_path' => $path ?? $fee->receipt_path,
        ]);

        return $fee->fresh();
    }

    public function verify(Fee $fee, User $staff, bool $approved, ?string $note = null): Fee
    {
        if ($fee->status !== FeeStatus::PendingVerification) {
            throw ValidationException::withMessages(['fee' => 'Only payments awaiting verification can be reviewed.']);
        }

        return DB::transaction(function () use ($fee, $staff, $approved, $note) {
            if ($approved) {
                $fee->update([
                    'status' => FeeStatus::Paid,
                    'paid_at' => now(),
                    'verified_by' => $staff->id,
                ]);
            } else {
                $fee->update([
                    'status' => FeeStatus::Unpaid,
                    'verified_by' => $staff->id,
                ]);
            }

            AppNotification::send(
                $fee->student,
                'fee',
                $approved ? 'Payment verified' : 'Payment rejected',
                $approved
                    ? "Payment for {$fee->reference} was verified."
                    : ($note ?: "Payment for {$fee->reference} could not be verified."),
                ['fee_id' => $fee->id],
            );

            return $fee->fresh();
        });
    }

    public function waive(Fee $fee, User $staff, ?string $note = null): Fee
    {
        $fee->update([
            'status' => FeeStatus::Waived,
            'verified_by' => $staff->id,
        ]);

        AppNotification::send(
            $fee->student,
            'fee',
            'Fee waived',
            "Invoice {$fee->reference} has been waived.",
            ['fee_id' => $fee->id],
        );

        return $fee->fresh();
    }

    /**
     * Flag unpaid invoices whose due date has passed.
     */
    public function markOverdue(): int
    {
        return Fee::query()
            ->where('status', FeeStatus::Unpaid->value)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => FeeStatus::Overdue->value]);
    }
}
