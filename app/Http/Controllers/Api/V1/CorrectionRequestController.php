<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCorrectionRequestRequest;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Resources\CorrectionRequestResource;
use App\Services\CorrectionRequestService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CorrectionRequestController extends Controller
{
    public function __construct(private readonly CorrectionRequestService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return CorrectionRequestResource::collection($this->service->list($request->only(['status']), $scope));
    }

    public function store(StoreCorrectionRequestRequest $request)
    {
        $correction = $this->service->submit($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Permintaan koreksi terkirim, menunggu tinjauan atasan.',
            'data' => new CorrectionRequestResource($correction),
        ], 201);
    }

    public function show(int $correctionRequest)
    {
        return response()->json(['success' => true, 'data' => new CorrectionRequestResource($this->service->find($correctionRequest))]);
    }

    public function review(ReviewCorrectionRequestRequest $request, int $correctionRequest)
    {
        try {
            $updated = $this->service->review(
                $correctionRequest,
                $request->validated()['decision'],
                $request->validated()['review_notes'] ?? null,
                $request->user(),
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => new CorrectionRequestResource($updated)]);
    }
}
