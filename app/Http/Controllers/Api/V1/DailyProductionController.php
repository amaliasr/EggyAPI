<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyProductionRequest;
use App\Http\Requests\UpdateDailyProductionRequest;
use App\Http\Resources\DailyProductionResource;
use App\Services\DailyProductionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DailyProductionController extends Controller
{
    public function __construct(private readonly DailyProductionService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return DailyProductionResource::collection(
            $this->service->list($request->only(['farm_source_id', 'date_from', 'date_to', 'recorded_by']), $scope)
        );
    }

    public function store(StoreDailyProductionRequest $request)
    {
        $production = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Produksi harian berhasil dicatat.',
            'data' => new DailyProductionResource($production),
        ], 201);
    }

    public function show(Request $request, int $dailyProduction)
    {
        $production = $this->service->find($dailyProduction);
        $user = $request->user();

        if (! $user->isAtasan() && $production->recorded_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data ini.'], 403);
        }

        return response()->json(['success' => true, 'data' => new DailyProductionResource($production)]);
    }

    public function update(UpdateDailyProductionRequest $request, int $dailyProduction)
    {
        try {
            $production = $this->service->update($dailyProduction, $request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['is_locked' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => new DailyProductionResource($production)]);
    }

    public function setLocked(Request $request, int $dailyProduction)
    {
        $data = $request->validate(['is_locked' => ['required', 'boolean']]);
        $production = $this->service->setLocked($dailyProduction, $data['is_locked'], $request->user());

        return response()->json(['success' => true, 'data' => new DailyProductionResource($production)]);
    }
}
