<?php

namespace App\Models;

use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'location',
        'total_rooms',
        'description',
        'image_path',
        'images',
        'is_active',
    ];

    protected $appends = ['image_url', 'image_urls'];

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

                return $urls ?: [asset('images/hostels/iqbal.jpg')];
            },
        );
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_rooms' => 'integer',
            'images' => 'array',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
