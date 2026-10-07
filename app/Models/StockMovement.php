<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'uuid', 'egg_category_id', 'movement_type', 'reference_type', 'reference_id',
        'quantity_pcs', 'weight_kg', 'balance_after_qty', 'balance_after_weight_kg',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['weight_kg' => 'decimal:2', 'balance_after_weight_kg' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class, 'egg_category_id');
    }
}
