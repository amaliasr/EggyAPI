<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEggCategoryRequest;
use App\Http\Requests\UpdateEggCategoryRequest;
use App\Http\Resources\EggCategoryResource;
use App\Services\EggCategoryService;
use Illuminate\Http\Request;

class EggCategoryController extends Controller
{
    public function __construct(private readonly EggCategoryService $service) {}

    public function index(Request $request)
    {
        return EggCategoryResource::collection($this->service->list($request->only(['is_active'])));
    }

    public function store(StoreEggCategoryRequest $request)
    {
        $category = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Kategori telur berhasil dibuat.',
            'data' => new EggCategoryResource($category),
        ], 201);
    }

    public function show(int $eggCategory)
    {
        return response()->json(['success' => true, 'data' => new EggCategoryResource($this->service->find($eggCategory))]);
    }

    public function update(UpdateEggCategoryRequest $request, int $eggCategory)
    {
        $category = $this->service->update($eggCategory, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Kategori telur berhasil diperbarui.',
            'data' => new EggCategoryResource($category),
        ]);
    }

    public function setStatus(Request $request, int $eggCategory)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $category = $this->service->setActive($eggCategory, $data['is_active'], $request->user());

        return response()->json(['success' => true, 'data' => new EggCategoryResource($category)]);
    }
}
