<?php

namespace Database\Seeders;

use App\Models\EggCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EggCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'BAGUS', 'name' => 'Telur Bagus', 'description' => 'Telur dengan kondisi baik, utuh.'],
            ['code' => 'BENTES', 'name' => 'Telur Bentes', 'description' => 'Telur retak/cacat, dijual terpisah.'],
        ];

        foreach ($categories as $category) {
            EggCategory::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['uuid' => (string) Str::uuid(), 'is_active' => true])
            );
        }
    }
}
