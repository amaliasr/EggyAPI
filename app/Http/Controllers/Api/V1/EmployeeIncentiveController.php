<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeIncentiveResource;
use App\Services\IncentiveService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeIncentiveController extends Controller
{
    public function __construct(private readonly IncentiveService $service) {}

    /** Insentif milik karyawan yang sedang login. */
    public function mine(Request $request)
    {
        return EmployeeIncentiveResource::collection($this->service->myIncentives($request->user()));
    }

    public function approve(Request $request, int $employeeIncentive)
    {
        try {
            $incentive = $this->service->approve($employeeIncentive, $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => new EmployeeIncentiveResource($incentive)]);
    }

    public function markPaid(Request $request, int $employeeIncentive)
    {
        try {
            $incentive = $this->service->markPaid($employeeIncentive, $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => new EmployeeIncentiveResource($incentive)]);
    }
}
