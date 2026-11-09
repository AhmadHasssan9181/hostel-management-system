<?php

namespace App\Models;

use App\Enums\FacilityStatus;
use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'location',
        'image_path',
        'opens_at',
        'closes_at',
        'capacity',
        'status',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'status' => FacilityStatus::class,
            'is_visible' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    protected $appends = ['image_url'];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => PublicImage::url($this->image_path),
        );
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
