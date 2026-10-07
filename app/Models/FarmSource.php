<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmSource extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name', 'description', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function dailyProductions()
    {
        return $this->hasMany(DailyProduction::class);
    }
}
