<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EggPickupItem extends Model
{
    protected $fillable = [
        'uuid', 'egg_pickup_id', 'egg_category_id', 'quantity_pcs', 'weight_kg',
        'crate_count', 'weight_sold_kg', 'weight_returned_kg',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'weight_sold_kg' => 'decimal:2',
            'weight_returned_kg' => 'decimal:2',
        ];
    }

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(EggPickup::class, 'egg_pickup_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class, 'egg_category_id');
    }

    public function remainingWeightKg(): float
    {
        return (float) $this->weight_kg - (float) $this->weight_sold_kg - (float) $this->weight_returned_kg;
    }
}
