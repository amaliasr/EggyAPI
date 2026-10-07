<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_atasan_can_create_employee(): void
    {
        $owner = User::factory()->atasan()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/employees', [
            'name' => 'Karyawan Baru',
            'email' => 'baru@eggyapps.test',
            'employee_code' => 'EMP-099',
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'baru@eggyapps.test');
        $this->assertDatabaseHas('users', ['email' => 'baru@eggyapps.test', 'role' => 'karyawan']);
        $this->assertDatabaseHas('employee_profiles', ['employee_code' => 'EMP-099']);
    }

    public function test_karyawan_cannot_access_employee_management(): void
    {
        $employee = User::factory()->karyawan()->create();

        $response = $this->actingAs($employee, 'sanctum')->getJson('/api/v1/employees');

        $response->assertStatus(403);
    }

    public function test_atasan_can_deactivate_employee(): void
    {
        $owner = User::factory()->atasan()->create();
        $employee = User::factory()->karyawan()->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/employees/{$employee->id}/status", ['is_active' => false]);

        $response->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'is_active' => false]);
    }
}
