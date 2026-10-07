<?php

namespace App\Services;

use App\Models\EggCategory;
use App\Models\EggPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Harga telur pakai price snapshot per transaksi (§7 dokumen rancangan):
 * riwayat harga TIDAK PERNAH dihapus/ditimpa. Harga baru selalu jadi baris
 * baru berstatus `terjadwal` (kalau effective_date > hari ini) atau langsung
 * `aktif` (kalau effective_date <= hari ini) — dan harga `aktif` lama untuk
 * kategori yang sama otomatis diturunkan jadi `kedaluwarsa`.
 *
 * activateDue() dipanggil di awal setiap query baca (list/active), jadi
 * aktivasi harga terjadwal jalan tanpa perlu cron job terpisah — pola yang
 * sama dengan PriceBoard::activateDue() di aplikasi web (lihat dokumen
 * status proyek web), supaya kedua sisi konsisten meski basis datanya sama.
 */
class PriceService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly NotificationDispatcher $notifier,
    ) {}

    public function list(array $filters): Collection
    {
        $this->activateDue();

        $query = EggPrice::query()->with(['category', 'setBy']);

        if (! empty($filters['egg_category_id'])) {
            $query->where('egg_category_id', $filters['egg_category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('effective_date')->orderByDesc('id')->get();
    }

    public function activePrices(): Collection
    {
        $this->activateDue();

        return EggCategory::query()->where('is_active', true)->orderBy('name')->get()
            ->map(function (EggCategory $category) {
                $price = EggPrice::where('egg_category_id', $category->id)
                    ->where('status', 'aktif')
                    ->orderByDesc('effective_date')
                    ->first();

                return [
                    'egg_category_id' => $category->id,
                    'egg_category_code' => $category->code,
                    'egg_category_name' => $category->name,
                    'price_per_kg' => $price ? (float) $price->price_per_kg : null,
                    'effective_date' => $price?->effective_date?->toDateString(),
                    'egg_price_id' => $price?->id,
                    'updated_at' => $price?->updated_at?->toIso8601String(),
                ];
            });
    }

    public function find(int $id): EggPrice
    {
        return EggPrice::with(['category', 'setBy'])->findOrFail($id);
    }

    /** Harga aktif untuk satu kategori — dipakai SaleService untuk price snapshot per transaksi. */
    public function activePriceFor(int $eggCategoryId): ?EggPrice
    {
        $this->activateDue();

        return EggPrice::where('egg_category_id', $eggCategoryId)
            ->where('status', 'aktif')
            ->orderByDesc('effective_date')
            ->first();
    }

    public function create(array $data, User $actor): EggPrice
    {
        return DB::transaction(function () use ($data, $actor) {
            $effectiveDate = $data['effective_date'];
            $isImmediatelyActive = $effectiveDate <= now()->toDateString();

            if ($isImmediatelyActive) {
                $this->expireCurrentActive((int) $data['egg_category_id']);
            }

            $price = EggPrice::create([
                'uuid' => (string) Str::uuid(),
                'egg_category_id' => $data['egg_category_id'],
                'price_per_kg' => $data['price_per_kg'],
                'effective_date' => $effectiveDate,
                'status' => $isImmediatelyActive ? 'aktif' : 'terjadwal',
                'set_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->auditLog->record($actor, 'create', $price, null, $price->only(['egg_category_id', 'price_per_kg', 'effective_date', 'status']));

            if ($isImmediatelyActive) {
                $this->notifyPriceActive($price);
            }

            return $price->load(['category', 'setBy']);
        });
    }

    /** Hanya boleh mengubah harga yang statusnya masih `terjadwal` — harga aktif/kedaluwarsa itu riwayat, tidak diedit. */
    public function update(int $id, array $data, User $actor): EggPrice
    {
        $price = $this->find($id);

        if ($price->status !== 'terjadwal') {
            throw new \RuntimeException('Hanya harga berstatus terjadwal yang boleh diubah. Harga aktif/kedaluwarsa adalah riwayat.');
        }

        $before = $price->only(['price_per_kg', 'effective_date', 'notes']);

        $price->fill([
            'price_per_kg' => $data['price_per_kg'] ?? $price->price_per_kg,
            'effective_date' => $data['effective_date'] ?? $price->effective_date,
            'notes' => $data['notes'] ?? $price->notes,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLog->record($actor, 'update', $price, $before, $price->only(['price_per_kg', 'effective_date', 'notes']));

        return $price->load(['category', 'setBy']);
    }

    /** Batalkan jadwal harga yang belum aktif (soft delete — riwayatnya tetap tersimpan). */
    public function cancel(int $id, User $actor): void
    {
        $price = $this->find($id);

        if ($price->status !== 'terjadwal') {
            throw new \RuntimeException('Hanya harga berstatus terjadwal yang boleh dibatalkan.');
        }

        $price->update(['updated_by' => $actor->id]);
        $price->delete();

        $this->auditLog->record($actor, 'cancel', $price, null, null);
    }

    /**
     * Aktifkan semua harga `terjadwal` yang effective_date-nya sudah tiba,
     * menurunkan harga `aktif` lama untuk kategori yang sama jadi `kedaluwarsa`.
     * Aman dipanggil berkali-kali (idempotent).
     */
    public function activateDue(): int
    {
        $due = EggPrice::where('status', 'terjadwal')
            ->where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date')
            ->get();

        $count = 0;

        foreach ($due as $price) {
            DB::transaction(function () use ($price, &$count) {
                $this->expireCurrentActive($price->egg_category_id);
                $price->update(['status' => 'aktif']);
                $this->notifyPriceActive($price);
                $count++;
            });
        }

        return $count;
    }

    private function expireCurrentActive(int $eggCategoryId): void
    {
        EggPrice::where('egg_category_id', $eggCategoryId)
            ->where('status', 'aktif')
            ->update(['status' => 'kedaluwarsa']);
    }

    private function notifyPriceActive(EggPrice $price): void
    {
        $price->loadMissing('category');
        $formatted = number_format((float) $price->price_per_kg, 0, ',', '.');

        $this->notifier->sendToActiveEmployees(
            'harga',
            'Harga telur diperbarui',
            "Mulai {$price->effective_date->translatedFormat('d F Y')}, harga {$price->category->name} Rp{$formatted}/kg.",
            ['egg_category_id' => $price->egg_category_id, 'price_per_kg' => (float) $price->price_per_kg]
        );
    }
}
