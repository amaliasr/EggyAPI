<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'uuid', 'sale_id', 'egg_category_id', 'egg_price_id', 'price_per_kg',
        'quantity_pcs', 'weight_kg', 'crate_count', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class, 'egg_category_id');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(EggPrice::class, 'egg_price_id');
    }
}
