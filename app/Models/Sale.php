<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'transaction_no', 'sale_date', 'employee_id', 'egg_pickup_id', 'customer_id',
        'customer_name', 'customer_phone', 'payment_method', 'payment_status',
        'total_amount', 'paid_amount', 'remaining_amount', 'proof_photo_path', 'notes',
        'is_locked', 'sync_status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'is_locked' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(EggPickup::class, 'egg_pickup_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(EggReturn::class);
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing(['items', 'payments']);
        $this->total_amount = $this->items->sum('subtotal');
        $this->paid_amount = $this->payments->sum('amount');
        $this->remaining_amount = max(0, $this->total_amount - $this->paid_amount);
        $this->payment_status = $this->remaining_amount <= 0
            ? 'lunas'
            : ($this->paid_amount > 0 ? 'sebagian' : 'belum_lunas');
        $this->save();
    }
}
