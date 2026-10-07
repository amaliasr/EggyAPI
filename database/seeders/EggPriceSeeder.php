<?php

namespace Database\Seeders;

use App\Models\EggCategory;
use App\Models\EggPrice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EggPriceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'atasan')->first();
        $bagus = EggCategory::where('code', 'BAGUS')->first();
        $bentes = EggCategory::where('code', 'BENTES')->first();

        // Riwayat harga sebelumnya (sudah kedaluwarsa)
        EggPrice::updateOrCreate(
            ['egg_category_id' => $bagus->id, 'effective_date' => '2026-09-01'],
            ['uuid' => (string) Str::uuid(), 'price_per_kg' => 27000, 'status' => 'kedaluwarsa', 'set_by' => $owner->id]
        );
        EggPrice::updateOrCreate(
            ['egg_category_id' => $bentes->id, 'effective_date' => '2026-09-01'],
            ['uuid' => (string) Str::uuid(), 'price_per_kg' => 17500, 'status' => 'kedaluwarsa', 'set_by' => $owner->id]
        );

        // Harga aktif berjalan (contoh notifikasi pada dokumen rancangan §2.2)
        EggPrice::updateOrCreate(
            ['egg_category_id' => $bagus->id, 'effective_date' => '2026-09-09'],
            ['uuid' => (string) Str::uuid(), 'price_per_kg' => 28000, 'status' => 'aktif', 'set_by' => $owner->id,
                'notes' => 'Harga telur diperbarui. Mulai 9 September 2026, harga telur bagus Rp28.000/kg dan telur bentes Rp18.000/kg.']
        );
        EggPrice::updateOrCreate(
            ['egg_category_id' => $bentes->id, 'effective_date' => '2026-09-09'],
            ['uuid' => (string) Str::uuid(), 'price_per_kg' => 18000, 'status' => 'aktif', 'set_by' => $owner->id]
        );
    }
}
