<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Resources\StockMovementResource;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private readonly StockService $service) {}

    /** Saldo terkini semua kategori — untuk kartu KPI dashboard. */
    public function balances()
    {
        return response()->json(['success' => true, 'data' => $this->service->balances()]);
    }

    public function movements(Request $request)
    {
        return StockMovementResource::collection(
            $this->service->movements($request->only(['egg_category_id', 'movement_type', 'date_from', 'date_to']))
        );
    }

    /** Penyesuaian manual (selisih stok fisik vs sistem) — hanya atasan, wajib disertai catatan alasan. */
    public function adjust(StoreStockAdjustmentRequest $request)
    {
        $data = $request->validated();

        $movement = $this->service->adjust(
            $data['egg_category_id'],
            $data['delta_quantity_pcs'],
            $data['delta_weight_kg'],
            $data['notes'],
            $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyesuaian stok tercatat.',
            'data' => new StockMovementResource($movement),
        ], 201);
    }
}
