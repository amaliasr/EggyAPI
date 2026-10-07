<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncentiveTier extends Model
{
    protected $fillable = [
        'uuid', 'incentive_program_id', 'tier_order', 'min_value', 'max_value',
        'rank', 'rate_type', 'rate_value',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(IncentiveProgram::class, 'incentive_program_id');
    }
}
