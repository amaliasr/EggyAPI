<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $service) {}

    public function index(Request $request)
    {
        $employees = $this->service->list($request->only(['is_active', 'search']));

        return EmployeeResource::collection($employees);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Akun karyawan berhasil dibuat.',
            'data' => new EmployeeResource($employee),
        ], 201);
    }

    public function show(int $employee)
    {
        return response()->json([
            'success' => true,
            'data' => new EmployeeResource($this->service->find($employee)),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, int $employee)
    {
        $updated = $this->service->update($employee, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diperbarui.',
            'data' => new EmployeeResource($updated),
        ]);
    }

    public function setStatus(Request $request, int $employee)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        $updated = $this->service->setActive($employee, $data['is_active'], $request->user());

        return response()->json([
            'success' => true,
            'message' => $data['is_active'] ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.',
            'data' => new EmployeeResource($updated),
        ]);
    }
}
