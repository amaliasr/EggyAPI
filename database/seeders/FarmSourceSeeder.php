<?php

namespace Database\Seeders;

use App\Models\FarmSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FarmSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Kelompok Lama', 'Kelompok Baru'] as $name) {
            FarmSource::updateOrCreate(
                ['name' => $name],
                ['uuid' => (string) Str::uuid(), 'is_active' => true]
            );
        }
    }
}
