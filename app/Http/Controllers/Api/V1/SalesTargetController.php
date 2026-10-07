<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalesTargetRequest;
use App\Http\Requests\UpdateSalesTargetRequest;
use App\Http\Resources\SalesTargetResource;
use App\Services\SalesTargetService;
use Illuminate\Http\Request;

class SalesTargetController extends Controller
{
    public function __construct(private readonly SalesTargetService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return SalesTargetResource::collection($this->service->list($request->only(['employee_id']), $scope));
    }

    public function store(StoreSalesTargetRequest $request)
    {
        $target = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Target penjualan berhasil dibuat.',
            'data' => new SalesTargetResource($target),
        ], 201);
    }

    public function show(Request $request, int $salesTarget)
    {
        $target = $this->service->find($salesTarget);

        $user = $request->user();
        if (! $user->isAtasan() && $target->employee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke target ini.'], 403);
        }

        $target->progress = $this->service->progress($target);

        return response()->json(['success' => true, 'data' => new SalesTargetResource($target)]);
    }

    public function update(UpdateSalesTargetRequest $request, int $salesTarget)
    {
        $target = $this->service->update($salesTarget, $request->validated(), $request->user());

        return response()->json(['success' => true, 'data' => new SalesTargetResource($target)]);
    }

    public function destroy(Request $request, int $salesTarget)
    {
        $this->service->delete($salesTarget, $request->user());

        return response()->json(['success' => true, 'message' => 'Target dihapus.']);
    }
}
