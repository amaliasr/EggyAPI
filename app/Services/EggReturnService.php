<?php

namespace App\Services;

use App\Models\EggPickup;
use App\Models\EggPickupItem;
use App\Models\EggReturn;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dua jalur retur, keduanya aktif berdampingan:
 *
 * 1) Jalur pengambilan (egg_pickup_id) — telur yang dibawa keliling tapi tidak
 *    laku, dikembalikan ke gudang. Menambah weight_returned_kg pada
 *    egg_pickup_items terkait, lalu status pengambilan dihitung ulang.
 *
 * 2) Jalur penjualan (sale_id) — pelanggan yang SUDAH membeli (tercatat di
 *    sales) lalu komplain/mengembalikan sebagian atau semua telurnya. Tidak
 *    menyentuh egg_pickups sama sekali (tidak ada pickup yang terlibat).
 *    refund_amount hanya DICATAT sebagai informasi — tidak otomatis mengubah
 *    total_amount/paid_amount pada Sale, supaya kebijakan refund (potong
 *    tunai vs. kredit vs. tukar barang) tetap keputusan manual atasan/kasir,
 *    bukan diasumsikan otomatis oleh sistem.
 *
 * Kedua jalur sama-sama menambah stok terpusat (movement_type
 * pengembalian_masuk) karena telur yang dikembalikan tetap fisik masuk lagi.
 */
class EggReturnService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditLogService $auditLog,
        private readonly CustomerService $customers,
    ) {}

    public function list(array $filters, ?User $scopeToUser = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = EggReturn::query()->with(['pickup', 'sale', 'customer', 'category', 'employee']);

        if ($scopeToUser) {
            $query->where('employee_id', $scopeToUser->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['egg_pickup_id'])) {
            $query->where('egg_pickup_id', $filters['egg_pickup_id']);
        }

        if (! empty($filters['sale_id'])) {
            $query->where('sale_id', $filters['sale_id']);
        }

        return $query->orderByDesc('return_date')->paginate($perPage);
    }

    public function find(int $id): EggReturn
    {
        return EggReturn::with(['pickup', 'sale', 'customer', 'category', 'employee'])->findOrFail($id);
    }

    public function create(array $data, User $actor): EggReturn
    {
        if (! empty($data['sale_id'])) {
            return $this->createFromSale($data, $actor);
        }

        return $this->createFromPickup($data, $actor);
    }

    /** Jalur lama (tidak diubah): retur stok pengambilan yang tidak laku. */
    private function createFromPickup(array $data, User $actor): EggReturn
    {
        return DB::transaction(function () use ($data, $actor) {
            $pickup = EggPickup::with('items')->lockForUpdate()->findOrFail($data['egg_pickup_id']);

            $pickupItem = $pickup->items->firstWhere('egg_category_id', (int) $data['egg_category_id']);

            if (! $pickupItem) {
                throw new \RuntimeException('Kategori telur ini tidak ada di pengambilan tersebut.');
            }

            /** @var EggPickupItem $pickupItem */
            $remaining = $pickupItem->remainingWeightKg();
            if ((float) $data['weight_kg'] > $remaining + 0.001) {
                throw new \RuntimeException("Berat retur melebihi sisa yang belum terjual ({$remaining} kg).");
            }

            $return = EggReturn::create([
                'uuid' => (string) Str::uuid(),
                'egg_pickup_id' => $pickup->id,
                'egg_category_id' => $data['egg_category_id'],
                'employee_id' => $actor->id,
                'return_date' => $data['return_date'] ?? now(),
                'quantity_pcs' => $data['quantity_pcs'],
                'weight_kg' => $data['weight_kg'],
                'crate_count' => $data['crate_count'] ?? 0,
                'reason' => $data['reason'],
                'created_by' => $actor->id,
            ]);

            $pickupItem->increment('weight_returned_kg', $data['weight_kg']);

            $this->stock->recordMovement(
                eggCategoryId: (int) $data['egg_category_id'],
                movementType: 'pengembalian_masuk',
                referenceType: 'egg_return',
                referenceId: $return->id,
                quantityPcs: (int) $data['quantity_pcs'],
                weightKg: (float) $data['weight_kg'],
                notes: "Retur dari {$pickup->pickup_code}: {$data['reason']}",
                actor: $actor,
            );

            $pickup->recalculateStatus();

            $this->auditLog->record($actor, 'create', $return, null, $return->only(['egg_pickup_id', 'weight_kg', 'reason']));

            return $return->load(['pickup', 'category', 'employee']);
        });
    }

    /** Jalur baru: pelanggan sudah beli (sale), lalu komplain/mengembalikan. */
    private function createFromSale(array $data, User $actor): EggReturn
    {
        $sale = Sale::with('items')->lockForUpdate()->findOrFail($data['sale_id']);

        $saleItem = $sale->items->firstWhere('egg_category_id', (int) $data['egg_category_id']);
        if (! $saleItem) {
            throw new \RuntimeException('Kategori telur ini tidak ada di transaksi penjualan tersebut.');
        }

        $alreadyReturned = (float) EggReturn::where('sale_id', $sale->id)
            ->where('egg_category_id', $data['egg_category_id'])
            ->sum('weight_kg');

        $remaining = round((float) $saleItem->weight_kg - $alreadyReturned, 2);
        if ((float) $data['weight_kg'] > $remaining + 0.001) {
            throw new \RuntimeException("Berat retur melebihi total yang dibeli pelanggan untuk kategori ini (sisa bisa diretur {$remaining} kg).");
        }

        // customer_id/new_customer eksplisit → dipakai. Kalau tidak diisi sama sekali,
        // ikut pelanggan yang sudah tercatat di transaksi penjualan aslinya (kalau ada).
        $customerId = $data['customer_id'] ?? null;
        $newCustomerData = $data['new_customer'] ?? null;
        if (! $customerId && ! $newCustomerData) {
            $customerId = $sale->customer_id;
        }
        $customer = $this->customers->resolveOrCreate($customerId, $newCustomerData, $actor);

        return DB::transaction(function () use ($data, $actor, $sale, $customer) {
            $return = EggReturn::create([
                'uuid' => (string) Str::uuid(),
                'sale_id' => $sale->id,
                'customer_id' => $customer?->id,
                'egg_category_id' => $data['egg_category_id'],
                'employee_id' => $actor->id,
                'return_date' => $data['return_date'] ?? now(),
                'quantity_pcs' => $data['quantity_pcs'],
                'weight_kg' => $data['weight_kg'],
                'crate_count' => $data['crate_count'] ?? 0,
                'refund_amount' => $data['refund_amount'] ?? null,
                'reason' => $data['reason'],
                'created_by' => $actor->id,
            ]);

            $this->stock->recordMovement(
                eggCategoryId: (int) $data['egg_category_id'],
                movementType: 'pengembalian_masuk',
                referenceType: 'egg_return',
                referenceId: $return->id,
                quantityPcs: (int) $data['quantity_pcs'],
                weightKg: (float) $data['weight_kg'],
                notes: "Retur dari transaksi {$sale->transaction_no}: {$data['reason']}",
                actor: $actor,
            );

            $this->auditLog->record($actor, 'create', $return, null, $return->only(['sale_id', 'customer_id', 'weight_kg', 'reason']));

            return $return->load(['sale', 'customer', 'category', 'employee']);
        });
    }
}
