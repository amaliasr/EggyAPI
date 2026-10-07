<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EggReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'egg_pickup_id', 'sale_id', 'customer_id', 'egg_category_id', 'employee_id',
        'return_date', 'quantity_pcs', 'weight_kg', 'crate_count', 'refund_amount', 'reason',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'weight_kg' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    /** Jalur lama: telur dibawa keliling (egg_pickup) tapi tidak laku, dikembalikan ke gudang. */
    public function pickup(): BelongsTo
    {
        return $this->belongsTo(EggPickup::class, 'egg_pickup_id');
    }

    /** Jalur baru: pelanggan sudah beli (sale), lalu komplain/mengembalikan sebagian/semua. */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class, 'egg_category_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
