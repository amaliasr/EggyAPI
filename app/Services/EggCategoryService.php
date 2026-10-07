<?php

namespace App\Services;

use App\Models\EggCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class EggCategoryService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function list(array $filters): Collection
    {
        $query = EggCategory::query();

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    public function find(int $id): EggCategory
    {
        return EggCategory::findOrFail($id);
    }

    public function create(array $data, User $actor): EggCategory
    {
        $category = EggCategory::create([
            'uuid' => (string) Str::uuid(),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);

        $this->auditLog->record($actor, 'create', $category, null, $category->only(['code', 'name']));

        return $category;
    }

    public function update(int $id, array $data, User $actor): EggCategory
    {
        $category = $this->find($id);
        $before = $category->only(['name', 'description']);

        $category->fill([
            'name' => $data['name'] ?? $category->name,
            'description' => $data['description'] ?? $category->description,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLog->record($actor, 'update', $category, $before, $category->only(['name', 'description']));

        return $category;
    }

    public function setActive(int $id, bool $isActive, User $actor): EggCategory
    {
        $category = $this->find($id);
        $category->update(['is_active' => $isActive, 'updated_by' => $actor->id]);

        $this->auditLog->record($actor, $isActive ? 'activate' : 'deactivate', $category, null, ['is_active' => $isActive]);

        return $category;
    }
}
