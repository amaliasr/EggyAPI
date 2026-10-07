<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncentiveProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'method', 'period_start', 'period_end', 'include_bentes',
        'include_piutang', 'percentage_value', 'target_value_kg', 'flat_bonus_amount',
        'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'include_bentes' => 'boolean',
            'include_piutang' => 'boolean',
        ];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(IncentiveTier::class)->orderBy('tier_order');
    }

    public function employeeIncentives(): HasMany
    {
        return $this->hasMany(EmployeeIncentive::class);
    }
}
