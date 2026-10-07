<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEggPriceRequest;
use App\Http\Requests\UpdateEggPriceRequest;
use App\Http\Resources\EggPriceResource;
use App\Services\PriceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PriceController extends Controller
{
    public function __construct(private readonly PriceService $service) {}

    public function index(Request $request)
    {
        return EggPriceResource::collection($this->service->list($request->only(['egg_category_id', 'status'])));
    }

    /** Harga aktif per kategori — dipakai layar "Harga Telur" & auto-refresh. */
    public function active()
    {
        return response()->json(['success' => true, 'data' => $this->service->activePrices()]);
    }

    public function store(StoreEggPriceRequest $request)
    {
        $price = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => $price->status === 'aktif' ? 'Harga baru langsung aktif.' : 'Harga terjadwal berhasil dibuat.',
            'data' => new EggPriceResource($price),
        ], 201);
    }

    public function show(int $price)
    {
        return response()->json(['success' => true, 'data' => new EggPriceResource($this->service->find($price))]);
    }

    public function update(UpdateEggPriceRequest $request, int $price)
    {
        try {
            $updated = $this->service->update($price, $request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => new EggPriceResource($updated)]);
    }

    public function destroy(Request $request, int $price)
    {
        try {
            $this->service->cancel($price, $request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'message' => 'Jadwal harga dibatalkan.']);
    }

    /** Pemicu manual (opsional) — list()/active() sudah menjalankan ini otomatis. */
    public function activateDue()
    {
        $count = $this->service->activateDue();

        return response()->json(['success' => true, 'message' => "{$count} harga terjadwal diaktifkan."]);
    }
}
