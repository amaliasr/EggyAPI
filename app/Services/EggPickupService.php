<?php

namespace App\Services;

use App\Models\EggPickup;
use App\Models\EggPickupItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Karyawan "mengambil" telur dari stok terpusat untuk dibawa berjualan
 * keliling. Setiap item langsung mengurangi stok (movement_type
 * pengambilan_keluar) — bukan saat penjualan dicatat, karena secara fisik
 * telurnya sudah keluar dari gudang saat pengambilan, bukan saat laku.
 * Penjualan & retur berikutnya hanya menggeser weight_sold_kg /
 * weight_returned_kg pada egg_pickup_items yang sama (lihat SaleService &
 * EggReturnService), tidak membuat pergerakan stok kedua kalinya.
 */
class EggPickupService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(array $filters, ?User $scopeToUser = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = EggPickup::query()->with(['items.category', 'employee']);

        if ($scopeToUser) {
            $query->where('employee_id', $scopeToUser->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('pickup_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('pickup_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('pickup_date')->orderByDesc('id')->paginate($perPage);
    }

    public function find(int $id): EggPickup
    {
        return EggPickup::with(['items.category', 'employee'])->findOrFail($id);
    }

    public function create(array $data, User $actor): EggPickup
    {
        if (! empty($data['uuid'])) {
            $existing = EggPickup::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing->load(['items.category', 'employee']);
            }
        }

        foreach ($data['items'] as $itemData) {
            $balance = $this->stock->currentBalance((int) $itemData['egg_category_id']);
            if ((float) $itemData['weight_kg'] > $balance['weight_kg']) {
                throw new \RuntimeException("Stok tidak cukup untuk kategori terpilih (sisa {$balance['weight_kg']} kg).");
            }
        }

        return DB::transaction(function () use ($data, $actor) {
            $pickup = EggPickup::create([
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'pickup_code' => $this->generatePickupCode(),
                'pickup_date' => $data['pickup_date'] ?? now(),
                'employee_id' => $actor->id,
                'destination_area' => $data['destination_area'] ?? null,
                'status' => 'dibawa',
                'notes' => $data['notes'] ?? null,
                'sync_status' => 'synced',
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $itemData) {
                $item = EggPickupItem::create([
                    'uuid' => (string) Str::uuid(),
                    'egg_pickup_id' => $pickup->id,
                    'egg_category_id' => $itemData['egg_category_id'],
                    'quantity_pcs' => $itemData['quantity_pcs'],
                    'weight_kg' => $itemData['weight_kg'],
                    'crate_count' => $itemData['crate_count'] ?? 0,
                ]);

                $this->stock->recordMovement(
                    eggCategoryId: $item->egg_category_id,
                    movementType: 'pengambilan_keluar',
                    referenceType: 'egg_pickup_item',
                    referenceId: $item->id,
                    quantityPcs: -1 * (int) $item->quantity_pcs,
                    weightKg: -1 * (float) $item->weight_kg,
                    notes: "Pengambilan {$pickup->pickup_code}",
                    actor: $actor,
                );
            }

            $this->auditLog->record($actor, 'create', $pickup, null, ['pickup_code' => $pickup->pickup_code]);

            return $pickup->load(['items.category', 'employee']);
        });
    }

    private function generatePickupCode(): string
    {
        $prefix = 'PU-'.now()->format('Ymd').'-';
        $todayCount = EggPickup::where('pickup_code', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($todayCount + 1), 3, '0', STR_PAD_LEFT);
    }
}
