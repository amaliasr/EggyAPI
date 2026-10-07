<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SalesTargetService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function list(array $filters, ?User $scopeToUser = null): Collection
    {
        $query = SalesTarget::query()->with('employee');

        if ($scopeToUser) {
            $query->where('employee_id', $scopeToUser->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->orderByDesc('period_start')->get();
    }

    public function find(int $id): SalesTarget
    {
        return SalesTarget::with('employee')->findOrFail($id);
    }

    public function create(array $data, User $actor): SalesTarget
    {
        $target = SalesTarget::create([
            'uuid' => (string) Str::uuid(),
            'employee_id' => $data['employee_id'] ?? null,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'target_weight_kg' => $data['target_weight_kg'],
            'target_omzet' => $data['target_omzet'] ?? null,
            'bonus_amount' => $data['bonus_amount'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->auditLog->record($actor, 'create', $target, null, $target->only(['employee_id', 'target_weight_kg']));

        return $target->load('employee');
    }

    public function update(int $id, array $data, User $actor): SalesTarget
    {
        $target = $this->find($id);
        $before = $target->only(['target_weight_kg', 'target_omzet', 'bonus_amount']);

        $target->fill(array_intersect_key($data, array_flip([
            'period_start', 'period_end', 'target_weight_kg', 'target_omzet', 'bonus_amount',
        ])))->forceFill(['updated_by' => $actor->id])->save();

        $this->auditLog->record($actor, 'update', $target, $before, $target->only(['target_weight_kg', 'target_omzet', 'bonus_amount']));

        return $target->load('employee');
    }

    public function delete(int $id, User $actor): void
    {
        $target = $this->find($id);
        $target->update(['updated_by' => $actor->id]);
        $target->delete();

        $this->auditLog->record($actor, 'delete', $target, null, null);
    }

    /** Progres tercapai vs target — dipakai karyawan melihat progres sendiri. */
    public function progress(SalesTarget $target): array
    {
        $query = Sale::whereDate('sale_date', '>=', $target->period_start)
            ->whereDate('sale_date', '<=', $target->period_end);

        if ($target->employee_id) {
            $query->where('employee_id', $target->employee_id);
        }

        $sales = $query->with('items')->get();

        $achievedWeightKg = $sales->flatMap->items->sum('weight_kg');
        $achievedOmzet = $sales->sum('total_amount');

        return [
            'target_weight_kg' => (float) $target->target_weight_kg,
            'achieved_weight_kg' => round((float) $achievedWeightKg, 2),
            'progress_percent' => $target->target_weight_kg > 0
                ? round(min(100, $achievedWeightKg / (float) $target->target_weight_kg * 100), 1)
                : 0.0,
            'target_omzet' => $target->target_omzet !== null ? (float) $target->target_omzet : null,
            'achieved_omzet' => round((float) $achievedOmzet, 2),
            'bonus_amount' => $target->bonus_amount !== null ? (float) $target->bonus_amount : null,
            'is_achieved' => $achievedWeightKg >= (float) $target->target_weight_kg,
        ];
    }
}
