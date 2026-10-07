<?php

namespace Database\Seeders;

use App\Models\DailyProduction;
use App\Models\DailyProductionItem;
use App\Models\EggCategory;
use App\Models\FarmSource;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh sesuai §17 dokumen rancangan (4-7 September 2026).
 * "Kelompok Lama" dan "Kelompok Baru" adalah sumber kandang (farm_sources),
 * bukan kategori kualitas — kategori kualitas tetap hanya Bagus & Bentes.
 */
class DailyProductionSeeder extends Seeder
{
    public function run(): void
    {
        $bagus = EggCategory::where('code', 'BAGUS')->firstOrFail();
        $bentes = EggCategory::where('code', 'BENTES')->firstOrFail();
        $lama = FarmSource::where('name', 'Kelompok Lama')->firstOrFail();
        $baru = FarmSource::where('name', 'Kelompok Baru')->firstOrFail();
        $budi = User::where('email', 'budi@eggyapps.test')->firstOrFail();
        $sari = User::where('email', 'sari@eggyapps.test')->firstOrFail();

        // [tanggal, sumber, karyawan, kategori, butir, kg, krat]
        $rows = [
            ['2026-09-04', $lama, $budi, $bagus, 660, 37.5, 4],
            ['2026-09-04', $baru, $sari, $bagus, 580, 32.5, 3],
            ['2026-09-04', null, $budi, $bentes, 35, 2.1, 0],

            ['2026-09-05', $lama, $budi, $bagus, 660, 37.5, 4],
            ['2026-09-05', $baru, $sari, $bagus, 595, 33.3, 3],
            ['2026-09-05', null, $budi, $bentes, 38, 2.2, 0],

            ['2026-09-06', $lama, $budi, $bagus, 660, 37.3, 4],
            ['2026-09-06', $baru, $sari, $bagus, 604, 34.1, 3],
            ['2026-09-06', null, $budi, $bentes, 36, 2.1, 0],

            ['2026-09-07', $lama, $budi, $bagus, 650, 36.0, 4],
            ['2026-09-07', $baru, $sari, $bagus, 625, 34.6, 3],
            ['2026-09-07', null, $budi, $bentes, 51, 3.0, 0],
        ];

        $balance = [
            $bagus->id => ['qty' => 0, 'weight' => 0.0],
            $bentes->id => ['qty' => 0, 'weight' => 0.0],
        ];

        foreach ($rows as [$date, $source, $employee, $category, $qty, $kg, $crates]) {
            $production = DailyProduction::create([
                'uuid' => (string) Str::uuid(),
                'production_date' => $date,
                'farm_source_id' => $source?->id,
                'recorded_by' => $employee->id,
                'sync_status' => 'synced',
                'created_by' => $employee->id,
            ]);

            DailyProductionItem::create([
                'uuid' => (string) Str::uuid(),
                'daily_production_id' => $production->id,
                'egg_category_id' => $category->id,
                'quantity_pcs' => $qty,
                'weight_kg' => $kg,
                'crate_count' => $crates,
                'estimated_weight_kg' => $crates * 10,
                'created_by' => $employee->id,
            ]);

            $production->recalculateTotals();

            $balance[$category->id]['qty'] += $qty;
            $balance[$category->id]['weight'] += $kg;

            StockMovement::create([
                'uuid' => (string) Str::uuid(),
                'egg_category_id' => $category->id,
                'movement_type' => 'produksi_masuk',
                'reference_type' => 'daily_production',
                'reference_id' => $production->id,
                'quantity_pcs' => $qty,
                'weight_kg' => $kg,
                'balance_after_qty' => $balance[$category->id]['qty'],
                'balance_after_weight_kg' => $balance[$category->id]['weight'],
                'created_by' => $employee->id,
            ]);
        }
    }
}
