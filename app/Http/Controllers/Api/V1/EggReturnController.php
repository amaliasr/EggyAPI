<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEggReturnRequest;
use App\Http\Resources\EggReturnResource;
use App\Services\EggReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EggReturnController extends Controller
{
    public function __construct(private readonly EggReturnService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return EggReturnResource::collection(
            $this->service->list($request->only(['employee_id', 'egg_pickup_id', 'sale_id']), $scope)
        );
    }

    public function store(StoreEggReturnRequest $request)
    {
        try {
            $return = $this->service->create($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['weight_kg' => [$e->getMessage()]]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Retur berhasil dicatat.',
            'data' => new EggReturnResource($return),
        ], 201);
    }

    public function show(Request $request, int $eggReturn)
    {
        $return = $this->service->find($eggReturn);
        $user = $request->user();

        if (! $user->isAtasan() && $return->employee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data ini.'], 403);
        }

        return response()->json(['success' => true, 'data' => new EggReturnResource($return)]);
    }
}
