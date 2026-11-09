<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostel_id',
        'room_number',
        'room_type',
        'image_path',
        'images',
        'capacity',
        'occupied_beds',
        'status',
        'floor',
        'monthly_rent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'occupied_beds' => 'integer',
            'floor' => 'integer',
            'monthly_rent' => 'decimal:2',
            'status' => RoomStatus::class,
            'images' => 'array',
        ];
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function occupants(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->where('status', BookingStatus::Approved->value);
    }

    protected $appends = ['available_beds', 'image_url', 'image_urls'];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => PublicImage::url($this->image_path),
        );
    }

    protected function imageUrls(): Attribute
    {
        return Attribute::make(
            get: function () {
                $paths = $this->images ?: [];

                if ($this->image_path) {
                    array_unshift($paths, $this->image_path);
                }

                $urls = collect($paths)
                    ->map(fn ($path) => PublicImage::url($path))
                    ->filter()
                    ->values()
                    ->all();

                return $urls ?: [asset('images/rooms/standard.jpg')];
            },
        );
    }

    protected function availableBeds(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->capacity - $this->occupied_beds),
        );
    }

    public function freeBeds(): int
    {
        return max(0, $this->capacity - $this->occupied_beds);
    }

    public function scopeWithAvailability(Builder $query): Builder
    {
        return $query->whereColumn('occupied_beds', '<', 'capacity')
            ->where('status', RoomStatus::Available->value);
    }

    public function syncStatus(): void
    {
        if ($this->status === RoomStatus::Maintenance) {
            return;
        }

        $this->status = $this->occupied_beds >= $this->capacity
            ? RoomStatus::Full
            : RoomStatus::Available;

        $this->save();
    }
}
