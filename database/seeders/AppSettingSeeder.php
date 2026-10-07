<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'estimasi_kg_per_krat' => ['value' => '10', 'description' => 'Estimasi berat per krat (kg) untuk pembanding, bukan patokan mutlak.'],
            'batas_toleransi_berat_persen' => ['value' => '15', 'description' => 'Ambang persentase selisih berat aktual vs estimasi sebelum diberi peringatan.'],
            'hari_kunci_data' => ['value' => '1', 'description' => 'Jumlah hari setelah input sebelum data terkunci otomatis.'],
        ];

        foreach ($settings as $key => $attrs) {
            AppSetting::updateOrCreate(['key' => $key], $attrs);
        }
    }
}
