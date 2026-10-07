<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uuid', 'name', 'phone', 'address', 'notes', 'created_by', 'updated_by'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function eggReturns(): HasMany
    {
        return $this->hasMany(EggReturn::class);
    }
}
