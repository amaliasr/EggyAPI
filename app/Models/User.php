<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'email', 'phone', 'password', 'role',
        'is_active', 'fcm_token', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAtasan(): bool
    {
        return $this->role === 'atasan';
    }

    public function isKaryawan(): bool
    {
        return $this->role === 'karyawan';
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function dailyProductions(): HasMany
    {
        return $this->hasMany(DailyProduction::class, 'recorded_by');
    }

    public function eggPickups(): HasMany
    {
        return $this->hasMany(EggPickup::class, 'employee_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'employee_id');
    }

    public function employeeIncentives(): HasMany
    {
        return $this->hasMany(EmployeeIncentive::class, 'employee_id');
    }

    public function salesTargets(): HasMany
    {
        return $this->hasMany(SalesTarget::class, 'employee_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
