<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmSourceRequest;
use App\Http\Requests\UpdateFarmSourceRequest;
use App\Http\Resources\FarmSourceResource;
use App\Services\FarmSourceService;
use Illuminate\Http\Request;

class FarmSourceController extends Controller
{
    public function __construct(private readonly FarmSourceService $service) {}

    public function index(Request $request)
    {
        return FarmSourceResource::collection($this->service->list($request->only(['is_active'])));
    }

    public function store(StoreFarmSourceRequest $request)
    {
        $source = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Sumber kandang berhasil dibuat.',
            'data' => new FarmSourceResource($source),
        ], 201);
    }

    public function show(int $farmSource)
    {
        return response()->json(['success' => true, 'data' => new FarmSourceResource($this->service->find($farmSource))]);
    }

    public function update(UpdateFarmSourceRequest $request, int $farmSource)
    {
        $source = $this->service->update($farmSource, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Sumber kandang berhasil diperbarui.',
            'data' => new FarmSourceResource($source),
        ]);
    }

    public function setStatus(Request $request, int $farmSource)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $source = $this->service->setActive($farmSource, $data['is_active'], $request->user());

        return response()->json(['success' => true, 'data' => new FarmSourceResource($source)]);
    }
}
