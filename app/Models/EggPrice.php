<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EggPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'egg_category_id', 'price_per_kg', 'effective_date', 'status', 'set_by', 'notes',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['price_per_kg' => 'decimal:2', 'effective_date' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class, 'egg_category_id');
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
