<?php

namespace App\Services;

use App\Models\EggCategory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ledger stok terpusat (§7 dokumen rancangan): stok TIDAK PERNAH disimpan
 * sebagai kolom saldo yang diedit langsung. Setiap perubahan stok (produksi
 * masuk, pengambilan keluar, pengembalian masuk, penyesuaian manual) selalu
 * menambah satu baris baru ke stock_movements, dengan balance_after dihitung
 * dari baris terakhir milik kategori yang sama. Jangan pernah UPDATE/DELETE
 * baris stock_movements yang sudah ada — itu akan merusak riwayat.
 */
class StockService
{
    /**
     * Tambah satu pergerakan stok dan kembalikan baris yang tersimpan.
     *
     * $quantityPcs / $weightKg harus SUDAH bertanda (+/-) sesuai arah:
     * produksi_masuk & pengembalian_masuk -> positif,
     * pengambilan_keluar -> negatif,
     * penyesuaian -> bisa positif atau negatif sesuai koreksi.
     */
    public function recordMovement(
        int $eggCategoryId,
        string $movementType,
        string $referenceType,
        int $referenceId,
        int $quantityPcs,
        float $weightKg,
        ?string $notes = null,
        ?User $actor = null,
    ): StockMovement {
        $last = StockMovement::where('egg_category_id', $eggCategoryId)
            ->orderByDesc('id')
            ->first();

        $balanceQty = ($last->balance_after_qty ?? 0) + $quantityPcs;
        $balanceWeight = round((float) ($last->balance_after_weight_kg ?? 0) + $weightKg, 2);

        return StockMovement::create([
            'uuid' => (string) Str::uuid(),
            'egg_category_id' => $eggCategoryId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity_pcs' => $quantityPcs,
            'weight_kg' => $weightKg,
            'balance_after_qty' => $balanceQty,
            'balance_after_weight_kg' => $balanceWeight,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
    }

    /** Saldo terkini satu kategori (0 kalau belum pernah ada pergerakan). */
    public function currentBalance(int $eggCategoryId): array
    {
        $last = StockMovement::where('egg_category_id', $eggCategoryId)
            ->orderByDesc('id')
            ->first();

        return [
            'quantity_pcs' => $last->balance_after_qty ?? 0,
            'weight_kg' => (float) ($last->balance_after_weight_kg ?? 0),
            'as_of' => $last?->created_at?->toIso8601String(),
        ];
    }

    /** Saldo terkini semua kategori aktif, untuk dashboard/laporan stok. */
    public function balances(): Collection
    {
        return EggCategory::query()->orderBy('name')->get()->map(function (EggCategory $category) {
            $balance = $this->currentBalance($category->id);

            return [
                'egg_category_id' => $category->id,
                'egg_category_code' => $category->code,
                'egg_category_name' => $category->name,
                'quantity_pcs' => $balance['quantity_pcs'],
                'weight_kg' => $balance['weight_kg'],
                'as_of' => $balance['as_of'],
            ];
        });
    }

    /** Riwayat pergerakan stok, untuk layar/laporan stok. */
    public function movements(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        $query = StockMovement::query()->with('category');

        if (! empty($filters['egg_category_id'])) {
            $query->where('egg_category_id', $filters['egg_category_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Penyesuaian manual (dipakai atasan lewat modul koreksi/stok).
     * $deltaQty/$deltaWeightKg boleh negatif untuk mengurangi stok.
     */
    public function adjust(
        int $eggCategoryId,
        int $deltaQtyPcs,
        float $deltaWeightKg,
        string $notes,
        User $actor,
    ): StockMovement {
        return $this->recordMovement(
            $eggCategoryId,
            'penyesuaian',
            'manual_adjustment',
            $actor->id,
            $deltaQtyPcs,
            $deltaWeightKg,
            $notes,
            $actor,
        );
    }
}
