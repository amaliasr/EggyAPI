<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProductionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'daily_production_id', 'egg_category_id', 'quantity_pcs', 'weight_kg',
        'crate_count', 'estimated_weight_kg', 'weight_variance_flagged', 'notes',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'estimated_weight_kg' => 'decimal:2',
            'weight_variance_flagged' => 'boolean',
        ];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(DailyProduction::class, 'daily_production_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class, 'egg_category_id');
    }
}
