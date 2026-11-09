<?php

namespace App\Models;

use App\Enums\FeeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'student_id',
        'booking_id',
        'type',
        'period',
        'amount',
        'fine',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'receipt_path',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fine' => 'decimal:2',
            'status' => FeeStatus::class,
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected $appends = ['receipt_url'];

    protected function receiptUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->receipt_path ? Storage::disk('public')->url($this->receipt_path) : null,
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function total(): float
    {
        return (float) $this->amount + (float) $this->fine;
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [
            FeeStatus::Unpaid->value,
            FeeStatus::Overdue->value,
            FeeStatus::PendingVerification->value,
        ]);
    }
}
