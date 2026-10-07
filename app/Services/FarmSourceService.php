<?php

namespace App\Services;

use App\Models\FarmSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class FarmSourceService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function list(array $filters): Collection
    {
        $query = FarmSource::query();

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    public function find(int $id): FarmSource
    {
        return FarmSource::findOrFail($id);
    }

    public function create(array $data, User $actor): FarmSource
    {
        $source = FarmSource::create([
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);

        $this->auditLog->record($actor, 'create', $source, null, $source->only(['name']));

        return $source;
    }

    public function update(int $id, array $data, User $actor): FarmSource
    {
        $source = $this->find($id);
        $before = $source->only(['name', 'description']);

        $source->fill([
            'name' => $data['name'] ?? $source->name,
            'description' => $data['description'] ?? $source->description,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLog->record($actor, 'update', $source, $before, $source->only(['name', 'description']));

        return $source;
    }

    public function setActive(int $id, bool $isActive, User $actor): FarmSource
    {
        $source = $this->find($id);
        $source->update(['is_active' => $isActive, 'updated_by' => $actor->id]);

        $this->auditLog->record($actor, $isActive ? 'activate' : 'deactivate', $source, null, ['is_active' => $isActive]);

        return $source;
    }
}
