<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalePaymentRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return SaleResource::collection(
            $this->service->list($request->only(['employee_id', 'payment_status', 'date_from', 'date_to']), $scope)
        );
    }

    public function store(StoreSaleRequest $request)
    {
        try {
            $sale = $this->service->create($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['items' => [$e->getMessage()]]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Penjualan berhasil dicatat.',
            'data' => new SaleResource($sale),
        ], 201);
    }

    public function show(Request $request, int $sale)
    {
        $found = $this->service->find($sale);
        $user = $request->user();

        if (! $user->isAtasan() && $found->employee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data ini.'], 403);
        }

        return response()->json(['success' => true, 'data' => new SaleResource($found)]);
    }

    public function addPayment(StoreSalePaymentRequest $request, int $sale)
    {
        try {
            $updated = $this->service->addPayment($sale, $request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['amount' => [$e->getMessage()]]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dicatat.',
            'data' => new SaleResource($updated),
        ]);
    }
}
