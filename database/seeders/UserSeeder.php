<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'amaliayuya@gmail.com'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Amalia',
                'phone' => '081200000000',
                'password' => 'password', // GANTI setelah instalasi awal
                'role' => 'atasan',
                'is_active' => true,
            ]
        );

        $employees = [
            ['name' => 'Budi Santoso', 'email' => 'budi@eggyapps.test', 'code' => 'EMP-001'],
            ['name' => 'Sari Wulandari', 'email' => 'sari@eggyapps.test', 'code' => 'EMP-002'],
            ['name' => 'Andi Saputra', 'email' => 'andi@eggyapps.test', 'code' => 'EMP-003'],
        ];

        foreach ($employees as $e) {
            $user = User::updateOrCreate(
                ['email' => $e['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $e['name'],
                    'phone' => '08211234567',
                    'password' => 'password', // GANTI setelah instalasi awal
                    'role' => 'karyawan',
                    'is_active' => true,
                ]
            );

            EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'uuid' => (string) Str::uuid(),
                    'employee_code' => $e['code'],
                    'join_date' => now()->subMonths(6),
                    'created_by' => $owner->id,
                ]
            );
        }
    }
}
