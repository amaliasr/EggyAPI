<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\DailyProductionItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Input produksi harian. Setiap item langsung menambah stok terpusat lewat
 * StockService (movement_type produksi_masuk) — jadi begitu produksi
 * disimpan, stok kategori terkait langsung bertambah, konsisten dengan
 * §7 dokumen rancangan (stok dari ledger, bukan kolom saldo).
 *
 * Idempotent lewat kolom `uuid`: kalau klien (mode offline) mengirim ulang
 * uuid yang sama (retry sinkronisasi), baris yang sudah ada dikembalikan
 * apa adanya, TANPA membuat item/pergerakan stok kedua kalinya.
 */
class DailyProductionService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(array $filters, ?User $scopeToUser = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = DailyProduction::query()->with(['items.category', 'farmSource', 'recordedBy']);

        if ($scopeToUser) {
            $query->where('recorded_by', $scopeToUser->id);
        } elseif (! empty($filters['recorded_by'])) {
            $query->where('recorded_by', $filters['recorded_by']);
        }

        if (! empty($filters['farm_source_id'])) {
            $query->where('farm_source_id', $filters['farm_source_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('production_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('production_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('production_date')->orderByDesc('id')->paginate($perPage);
    }

    public function find(int $id): DailyProduction
    {
        return DailyProduction::with(['items.category', 'farmSource', 'recordedBy'])->findOrFail($id);
    }

    public function create(array $data, User $actor): DailyProduction
    {
        if (! empty($data['uuid'])) {
            $existing = DailyProduction::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing->load(['items.category', 'farmSource', 'recordedBy']);
            }
        }

        return DB::transaction(function () use ($data, $actor) {
            $production = DailyProduction::create([
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'production_date' => $data['production_date'],
                'farm_source_id' => $data['farm_source_id'] ?? null,
                'recorded_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
                'location_lat' => $data['location_lat'] ?? null,
                'location_lng' => $data['location_lng'] ?? null,
                'sync_status' => 'synced',
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $itemData) {
                $item = DailyProductionItem::create([
                    'uuid' => (string) Str::uuid(),
                    'daily_production_id' => $production->id,
                    'egg_category_id' => $itemData['egg_category_id'],
                    'quantity_pcs' => $itemData['quantity_pcs'],
                    'weight_kg' => $itemData['weight_kg'],
                    'crate_count' => $itemData['crate_count'] ?? 0,
                    'estimated_weight_kg' => $itemData['estimated_weight_kg'] ?? null,
                    'weight_variance_flagged' => $this->isVarianceFlagged($itemData),
                    'notes' => $itemData['notes'] ?? null,
                    'created_by' => $actor->id,
                ]);

                $this->stock->recordMovement(
                    eggCategoryId: $item->egg_category_id,
                    movementType: 'produksi_masuk',
                    referenceType: 'daily_production_item',
                    referenceId: $item->id,
                    quantityPcs: (int) $item->quantity_pcs,
                    weightKg: (float) $item->weight_kg,
                    notes: "Produksi harian #{$production->id}",
                    actor: $actor,
                );
            }

            $production->recalculateTotals();

            $this->auditLog->record($actor, 'create', $production, null, [
                'production_date' => (string) $production->production_date,
                'total_weight_kg' => (float) $production->total_weight_kg,
            ]);

            return $production->load(['items.category', 'farmSource', 'recordedBy']);
        });
    }

    /** Selisih >10% dari estimasi ditandai untuk ditinjau (dipakai UI karyawan sebagai peringatan halus). */
    private function isVarianceFlagged(array $itemData): bool
    {
        if (empty($itemData['estimated_weight_kg']) || (float) $itemData['estimated_weight_kg'] <= 0) {
            return false;
        }

        $estimated = (float) $itemData['estimated_weight_kg'];
        $actual = (float) $itemData['weight_kg'];

        return abs($actual - $estimated) / $estimated > 0.1;
    }

    /**
     * Update TERBATAS pada catatan & sumber kandang — item & stok yang sudah
     * tercatat tidak diedit langsung di sini (kalau salah input, ajukan lewat
     * modul Koreksi supaya ada jejak audit dan stok bisa dikoreksi dengan benar).
     */
    public function update(int $id, array $data, User $actor): DailyProduction
    {
        $production = $this->find($id);

        if ($production->is_locked) {
            throw new \RuntimeException('Produksi ini sudah dikunci dan tidak bisa diubah. Ajukan koreksi.');
        }

        $before = $production->only(['notes', 'farm_source_id']);

        $production->fill([
            'notes' => $data['notes'] ?? $production->notes,
            'farm_source_id' => $data['farm_source_id'] ?? $production->farm_source_id,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLog->record($actor, 'update', $production, $before, $production->only(['notes', 'farm_source_id']));

        return $production;
    }

    public function setLocked(int $id, bool $locked, User $actor): DailyProduction
    {
        $production = $this->find($id);
        $production->update(['is_locked' => $locked, 'updated_by' => $actor->id]);

        $this->auditLog->record($actor, $locked ? 'lock' : 'unlock', $production, null, ['is_locked' => $locked]);

        return $production;
    }
}
