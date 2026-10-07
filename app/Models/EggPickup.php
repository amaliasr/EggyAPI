<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EggPickup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'pickup_code', 'pickup_date', 'employee_id', 'destination_area', 'status',
        'notes', 'sync_status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['pickup_date' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(EggPickupItem::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(EggReturn::class);
    }

    /**
     * Turunkan `status` dari sisa berat di egg_pickup_items (weight_kg dikurangi
     * weight_sold_kg dan weight_returned_kg). Dipanggil ulang setiap kali
     * penjualan atau retur menyentuh salah satu item milik pengambilan ini.
     */
    public function recalculateStatus(): void
    {
        $this->loadMissing('items');

        $totalWeight = (float) $this->items->sum('weight_kg');
        $totalSold = (float) $this->items->sum('weight_sold_kg');
        $totalReturned = (float) $this->items->sum('weight_returned_kg');
        $remaining = round($totalWeight - $totalSold - $totalReturned, 2);

        $this->status = match (true) {
            $totalSold <= 0.0 && $totalReturned <= 0.0 => 'dibawa',
            $remaining > 0.0 => 'sebagian_terjual',
            $totalSold > 0.0 => 'terjual',
            default => 'dikembalikan',
        };

        $this->save();
    }
}
