<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyProduction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'production_date', 'farm_source_id', 'recorded_by', 'total_qty_pcs',
        'total_weight_kg', 'total_crates', 'notes', 'photo_path', 'location_lat', 'location_lng',
        'is_locked', 'sync_status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'total_weight_kg' => 'decimal:2',
            'is_locked' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyProductionItem::class);
    }

    public function farmSource(): BelongsTo
    {
        return $this->belongsTo(FarmSource::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');
        $this->total_qty_pcs = $this->items->sum('quantity_pcs');
        $this->total_weight_kg = $this->items->sum('weight_kg');
        $this->total_crates = $this->items->sum('crate_count');
        $this->save();
    }
}
