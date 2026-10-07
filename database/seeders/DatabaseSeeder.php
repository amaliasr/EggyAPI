<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EggCategorySeeder::class,
            FarmSourceSeeder::class,
            AppSettingSeeder::class,
            UserSeeder::class,
            EggPriceSeeder::class,
            DailyProductionSeeder::class,
        ]);
    }
}
