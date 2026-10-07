<?php

namespace App\Repositories\Eloquent;

use App\Models\EmployeeProfile;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query()->where('role', 'karyawan')->with('employeeProfile');

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findOrFail(int $id): User
    {
        return User::where('role', 'karyawan')->with('employeeProfile')->findOrFail($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role' => 'karyawan',
                'is_active' => true,
                'created_by' => $data['created_by'] ?? null,
            ]);

            EmployeeProfile::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'employee_code' => $data['employee_code'],
                'join_date' => $data['join_date'] ?? now(),
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            return $user->load('employeeProfile');
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data['name'] ?? $user->name,
                'phone' => $data['phone'] ?? $user->phone,
                'updated_by' => $data['updated_by'] ?? null,
            ])->save();

            if ($user->employeeProfile) {
                $user->employeeProfile->fill([
                    'employee_code' => $data['employee_code'] ?? $user->employeeProfile->employee_code,
                    'address' => $data['address'] ?? $user->employeeProfile->address,
                    'notes' => $data['notes'] ?? $user->employeeProfile->notes,
                    'updated_by' => $data['updated_by'] ?? null,
                ])->save();
            }

            return $user->fresh('employeeProfile');
        });
    }

    public function setActive(User $user, bool $isActive): User
    {
        $user->update(['is_active' => $isActive]);

        return $user->fresh('employeeProfile');
    }
}
