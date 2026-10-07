<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $service) {}

    public function index(Request $request)
    {
        $paginator = $request->user()->isAtasan() ? $this->service->listAll() : $this->service->listActive();

        return AnnouncementResource::collection($paginator);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $announcement = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dibuat.',
            'data' => new AnnouncementResource($announcement),
        ], 201);
    }

    public function show(int $announcement)
    {
        return response()->json(['success' => true, 'data' => new AnnouncementResource($this->service->find($announcement))]);
    }

    public function update(UpdateAnnouncementRequest $request, int $announcement)
    {
        $updated = $this->service->update($announcement, $request->validated(), $request->user());

        return response()->json(['success' => true, 'data' => new AnnouncementResource($updated)]);
    }

    public function setStatus(Request $request, int $announcement)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $updated = $this->service->setActive($announcement, $data['is_active'], $request->user());

        return response()->json(['success' => true, 'data' => new AnnouncementResource($updated)]);
    }
}
