<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncentiveProgramRequest;
use App\Http\Requests\UpdateIncentiveProgramRequest;
use App\Http\Resources\EmployeeIncentiveResource;
use App\Http\Resources\IncentiveProgramResource;
use App\Services\IncentiveService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IncentiveProgramController extends Controller
{
    public function __construct(private readonly IncentiveService $service) {}

    public function index(Request $request)
    {
        return IncentiveProgramResource::collection($this->service->listPrograms($request->only(['status'])));
    }

    public function store(StoreIncentiveProgramRequest $request)
    {
        $program = $this->service->createProgram($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Program insentif berhasil dibuat.',
            'data' => new IncentiveProgramResource($program),
        ], 201);
    }

    public function show(int $incentiveProgram)
    {
        return response()->json(['success' => true, 'data' => new IncentiveProgramResource($this->service->findProgram($incentiveProgram))]);
    }

    public function update(UpdateIncentiveProgramRequest $request, int $incentiveProgram)
    {
        try {
            $program = $this->service->updateProgram($incentiveProgram, $request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => new IncentiveProgramResource($program)]);
    }

    public function setStatus(Request $request, int $incentiveProgram)
    {
        $data = $request->validate(['status' => ['required', 'in:draft,aktif,ditutup']]);
        $program = $this->service->setStatus($incentiveProgram, $data['status'], $request->user());

        return response()->json(['success' => true, 'data' => new IncentiveProgramResource($program)]);
    }

    /** Hitung ulang estimasi insentif semua karyawan untuk program ini (idempotent — upsert per karyawan). */
    public function calculate(Request $request, int $incentiveProgram)
    {
        $results = $this->service->calculate($incentiveProgram, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Estimasi insentif dihitung ulang.',
            'data' => EmployeeIncentiveResource::collection($results),
        ]);
    }
}
