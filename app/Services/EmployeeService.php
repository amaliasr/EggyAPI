<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $repository,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(array $filters)
    {
        return $this->repository->paginate($filters);
    }

    public function find(int $id): User
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data, User $actor): User
    {
        $data['password'] = $data['password'] ?? str()->random(10);
        $data['created_by'] = $actor->id;

        $employee = $this->repository->create($data);

        $this->auditLog->record($actor, 'create', $employee, null, $employee->only(['name', 'email']));

        return $employee;
    }

    public function update(int $id, array $data, User $actor): User
    {
        $employee = $this->repository->findOrFail($id);
        $before = $employee->only(['name', 'phone']);

        $data['updated_by'] = $actor->id;
        $employee = $this->repository->update($employee, $data);

        $this->auditLog->record($actor, 'update', $employee, $before, $employee->only(['name', 'phone']));

        return $employee;
    }

    public function setActive(int $id, bool $isActive, User $actor): User
    {
        $employee = $this->repository->findOrFail($id);
        $employee = $this->repository->setActive($employee, $isActive);

        $this->auditLog->record(
            $actor,
            $isActive ? 'activate' : 'deactivate',
            $employee,
            ['is_active' => ! $isActive],
            ['is_active' => $isActive]
        );

        return $employee;
    }
}
