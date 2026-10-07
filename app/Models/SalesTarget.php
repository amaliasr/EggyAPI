<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTarget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'employee_id', 'period_start', 'period_end', 'target_weight_kg',
        'target_omzet', 'bonus_amount', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
