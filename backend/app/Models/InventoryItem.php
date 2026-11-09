<?php

namespace App\Models;

use App\Enums\InventoryCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostel_id',
        'name',
        'category',
        'quantity',
        'unit',
        'threshold',
        'condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'threshold' => 'integer',
            'condition' => InventoryCondition::class,
        ];
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->threshold;
    }
}
