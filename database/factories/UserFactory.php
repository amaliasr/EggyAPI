<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '0812'.$this->faker->numerify('########'),
            'password' => 'password',
            'role' => 'karyawan',
            'is_active' => true,
        ];
    }

    public function atasan(): static
    {
        return $this->state(fn () => ['role' => 'atasan']);
    }

    public function karyawan(): static
    {
        return $this->state(fn () => ['role' => 'karyawan']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
