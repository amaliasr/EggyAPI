<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeIncentive extends Model
{
    protected $fillable = [
        'uuid', 'incentive_program_id', 'employee_id', 'total_weight_kg', 'total_omzet',
        'rank', 'calculated_amount', 'status', 'approved_by', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total_weight_kg' => 'decimal:2',
            'total_omzet' => 'decimal:2',
            'calculated_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(IncentiveProgram::class, 'incentive_program_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
