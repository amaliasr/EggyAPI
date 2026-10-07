<?php

namespace App\Services;

use App\Models\EggPickup;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pencatatan penjualan. Harga per item selalu SNAPSHOT dari harga aktif saat
 * transaksi dibuat (egg_price_id + price_per_kg disalin ke sale_items) —
 * kalau harga berubah setelahnya, transaksi lama tidak ikut berubah
 * (§7 dokumen rancangan).
 *
 * Kalau transaksi terhubung ke egg_pickup_id (kasus umum: karyawan jual dari
 * telur yang sudah dibawa keliling), stok TIDAK dikurangi lagi di sini —
 * sudah dikurangi saat pengambilan dibuat. Yang terjadi hanya menambah
 * weight_sold_kg pada egg_pickup_items terkait. Kalau tanpa pickup (penjualan
 * langsung dari gudang), baru stok dikurangi di sini.
 */
class SaleService
{
    public function __construct(
        private readonly PriceService $prices,
        private readonly StockService $stock,
        private readonly AuditLogService $auditLog,
        private readonly CustomerService $customers,
    ) {}

    public function list(array $filters, ?User $scopeToUser = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Sale::query()->with(['items.category', 'payments', 'employee', 'customer']);

        if ($scopeToUser) {
            $query->where('employee_id', $scopeToUser->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('sale_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sale_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('sale_date')->orderByDesc('id')->paginate($perPage);
    }

    public function find(int $id): Sale
    {
        return Sale::with(['items.category', 'payments', 'employee', 'pickup', 'customer'])->findOrFail($id);
    }

    public function create(array $data, User $actor): Sale
    {
        if (! empty($data['uuid'])) {
            $existing = Sale::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing->load(['items.category', 'payments', 'employee', 'customer']);
            }
        }

        $pickup = null;
        if (! empty($data['egg_pickup_id'])) {
            $pickup = EggPickup::with('items')->lockForUpdate()->findOrFail($data['egg_pickup_id']);

            foreach ($data['items'] as $itemData) {
                $pickupItem = $pickup->items->firstWhere('egg_category_id', (int) $itemData['egg_category_id']);
                if (! $pickupItem) {
                    throw new \RuntimeException('Kategori telur ini tidak ada di pengambilan yang dipilih.');
                }
                $remaining = $pickupItem->remainingWeightKg();
                if ((float) $itemData['weight_kg'] > $remaining + 0.001) {
                    throw new \RuntimeException("Berat jual melebihi sisa telur yang dibawa ({$remaining} kg).");
                }
            }
        } else {
            foreach ($data['items'] as $itemData) {
                $balance = $this->stock->currentBalance((int) $itemData['egg_category_id']);
                if ((float) $itemData['weight_kg'] > $balance['weight_kg']) {
                    throw new \RuntimeException("Stok tidak cukup untuk kategori terpilih (sisa {$balance['weight_kg']} kg).");
                }
            }
        }

        // Diresolusi SEBELUM transaksi utama supaya kalau new_customer gagal
        // (mis. validasi lolos tapi ada race unik lain), penjualan belum terlanjur dibuat.
        $customer = $this->customers->resolveOrCreate(
            $data['customer_id'] ?? null,
            $data['new_customer'] ?? null,
            $actor,
        );

        return DB::transaction(function () use ($data, $actor, $pickup, $customer) {
            $sale = Sale::create([
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'transaction_no' => $this->generateTransactionNo(),
                'sale_date' => $data['sale_date'] ?? now(),
                'employee_id' => $actor->id,
                'egg_pickup_id' => $pickup?->id,
                'customer_id' => $customer?->id,
                // Snapshot teks: kalau ada pelanggan tercatat, salin namanya; kalau tidak
                // (walk-in), pakai teks bebas yang diketik langsung di form.
                'customer_name' => $customer?->name ?? $data['customer_name'] ?? null,
                'customer_phone' => $customer?->phone ?? $data['customer_phone'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'belum_lunas',
                'proof_photo_path' => $data['proof_photo_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'sync_status' => 'synced',
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $itemData) {
                $activePrice = $this->prices->activePriceFor((int) $itemData['egg_category_id']);
                if (! $activePrice) {
                    throw new \RuntimeException('Belum ada harga aktif untuk salah satu kategori telur. Set harga dulu di modul Harga Telur.');
                }

                $pricePerKg = $itemData['price_per_kg'] ?? (float) $activePrice->price_per_kg;
                $subtotal = round($pricePerKg * (float) $itemData['weight_kg'], 2);

                SaleItem::create([
                    'uuid' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'egg_category_id' => $itemData['egg_category_id'],
                    'egg_price_id' => $activePrice->id,
                    'price_per_kg' => $pricePerKg,
                    'quantity_pcs' => $itemData['quantity_pcs'],
                    'weight_kg' => $itemData['weight_kg'],
                    'crate_count' => $itemData['crate_count'] ?? 0,
                    'subtotal' => $subtotal,
                ]);

                if ($pickup) {
                    $pickupItem = $pickup->items->firstWhere('egg_category_id', (int) $itemData['egg_category_id']);
                    $pickupItem->increment('weight_sold_kg', $itemData['weight_kg']);
                } else {
                    $this->stock->recordMovement(
                        eggCategoryId: (int) $itemData['egg_category_id'],
                        movementType: 'pengambilan_keluar',
                        referenceType: 'sale_direct',
                        referenceId: $sale->id,
                        quantityPcs: -1 * (int) $itemData['quantity_pcs'],
                        weightKg: -1 * (float) $itemData['weight_kg'],
                        notes: "Penjualan langsung {$sale->transaction_no}",
                        actor: $actor,
                    );
                }
            }

            foreach ($data['payments'] ?? [] as $paymentData) {
                SalePayment::create([
                    'uuid' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'payment_date' => $paymentData['payment_date'] ?? now(),
                    'amount' => $paymentData['amount'],
                    'method' => $paymentData['method'],
                    'proof_photo_path' => $paymentData['proof_photo_path'] ?? null,
                    'notes' => $paymentData['notes'] ?? null,
                    'created_by' => $actor->id,
                ]);
            }

            $sale->recalculateTotals();

            if ($pickup) {
                $pickup->recalculateStatus();
            }

            $this->auditLog->record($actor, 'create', $sale, null, [
                'transaction_no' => $sale->transaction_no,
                'total_amount' => (float) $sale->total_amount,
            ]);

            return $sale->load(['items.category', 'payments', 'employee', 'customer']);
        });
    }

    public function addPayment(int $saleId, array $data, User $actor): Sale
    {
        return DB::transaction(function () use ($saleId, $data, $actor) {
            $sale = Sale::with(['items', 'payments'])->lockForUpdate()->findOrFail($saleId);

            if ($sale->payment_status === 'lunas') {
                throw new \RuntimeException('Transaksi ini sudah lunas.');
            }

            SalePayment::create([
                'uuid' => (string) Str::uuid(),
                'sale_id' => $sale->id,
                'payment_date' => $data['payment_date'] ?? now(),
                'amount' => $data['amount'],
                'method' => $data['method'],
                'proof_photo_path' => $data['proof_photo_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $sale->recalculateTotals();

            $this->auditLog->record($actor, 'payment', $sale, null, ['amount' => $data['amount']]);

            return $sale->load(['items.category', 'payments', 'employee', 'customer']);
        });
    }

    private function generateTransactionNo(): string
    {
        $prefix = 'TX-'.now()->format('Ymd').'-';
        $todayCount = Sale::where('transaction_no', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);
    }
}
