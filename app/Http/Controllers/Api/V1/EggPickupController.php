<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEggPickupRequest;
use App\Http\Resources\EggPickupResource;
use App\Services\EggPickupService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EggPickupController extends Controller
{
    public function __construct(private readonly EggPickupService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return EggPickupResource::collection(
            $this->service->list($request->only(['employee_id', 'status', 'date_from', 'date_to']), $scope)
        );
    }

    public function store(StoreEggPickupRequest $request)
    {
        try {
            $pickup = $this->service->create($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['items' => [$e->getMessage()]]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengambilan telur berhasil dicatat.',
            'data' => new EggPickupResource($pickup),
        ], 201);
    }

    public function show(Request $request, int $eggPickup)
    {
        $pickup = $this->service->find($eggPickup);
        $user = $request->user();

        if (! $user->isAtasan() && $pickup->employee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data ini.'], 403);
        }

        return response()->json(['success' => true, 'data' => new EggPickupResource($pickup)]);
    }
}
