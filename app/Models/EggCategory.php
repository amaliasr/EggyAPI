<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EggCategory extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'code', 'name', 'description', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function prices()
    {
        return $this->hasMany(EggPrice::class);
    }

    public function activePrice()
    {
        return $this->hasOne(EggPrice::class)->where('status', 'aktif')->latestOfMany('effective_date');
    }
}
