<?php

namespace App\Services;

use App\Models\EggCategory;
use App\Models\EmployeeIncentive;
use App\Models\IncentiveProgram;
use App\Models\IncentiveTier;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Insentif karyawan, 4 metode (§ ringkasan dokumen rancangan):
 * - berat: total kg terjual per karyawan dalam periode, dicocokkan ke tier (min_value..max_value).
 * - persentase_omzet: total omzet (piutang diikutkan/tidak sesuai include_piutang) dikali percentage_value.
 * - peringkat: karyawan diurutkan berdasarkan total kg, tier dicocokkan lewat kolom `rank`.
 * - target: dibandingkan ke target_value_kg program; tercapai = flat_bonus_amount penuh.
 *
 * CATATAN PENTING: ini implementasi terbaik saya berdasarkan ringkasan
 * keputusan desain yang tersimpan ("4 metode: berat, persentase omzet, bonus
 * peringkat, target") — dokumen Tahap 1 asli yang berisi rumus detail per
 * metode tidak ikut terbaca ulang di sesi ini. Sebelum dipakai untuk
 * menghitung insentif SUNGGUHAN, mohon dicocokkan sekali lagi dengan dokumen
 * "Analisis & Rancangan" asli — kalau ada perbedaan rumus, beri tahu saya,
 * gampang disesuaikan karena logikanya terkumpul di satu method calculate().
 */
class IncentiveService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly NotificationDispatcher $notifier,
    ) {}

    public function listPrograms(array $filters): Collection
    {
        $query = IncentiveProgram::query()->with('tiers');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('period_start')->get();
    }

    public function findProgram(int $id): IncentiveProgram
    {
        return IncentiveProgram::with(['tiers', 'employeeIncentives.employee'])->findOrFail($id);
    }

    public function createProgram(array $data, User $actor): IncentiveProgram
    {
        return DB::transaction(function () use ($data, $actor) {
            $program = IncentiveProgram::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'method' => $data['method'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'include_bentes' => $data['include_bentes'] ?? false,
                'include_piutang' => $data['include_piutang'] ?? false,
                'percentage_value' => $data['percentage_value'] ?? null,
                'target_value_kg' => $data['target_value_kg'] ?? null,
                'flat_bonus_amount' => $data['flat_bonus_amount'] ?? null,
                'status' => 'draft',
                'created_by' => $actor->id,
            ]);

            foreach ($data['tiers'] ?? [] as $index => $tierData) {
                IncentiveTier::create([
                    'uuid' => (string) Str::uuid(),
                    'incentive_program_id' => $program->id,
                    'tier_order' => $tierData['tier_order'] ?? $index + 1,
                    'min_value' => $tierData['min_value'] ?? null,
                    'max_value' => $tierData['max_value'] ?? null,
                    'rank' => $tierData['rank'] ?? null,
                    'rate_type' => $tierData['rate_type'],
                    'rate_value' => $tierData['rate_value'],
                ]);
            }

            $this->auditLog->record($actor, 'create', $program, null, $program->only(['name', 'method']));

            return $program->load('tiers');
        });
    }

    public function updateProgram(int $id, array $data, User $actor): IncentiveProgram
    {
        $program = $this->findProgram($id);

        if ($program->status !== 'draft') {
            throw new \RuntimeException('Program yang sudah aktif/ditutup tidak bisa diubah datanya — tutup dan buat program baru.');
        }

        $before = $program->only(['name', 'period_start', 'period_end']);

        $program->fill(array_intersect_key($data, array_flip([
            'name', 'period_start', 'period_end', 'include_bentes', 'include_piutang',
            'percentage_value', 'target_value_kg', 'flat_bonus_amount',
        ])))->forceFill(['updated_by' => $actor->id])->save();

        $this->auditLog->record($actor, 'update', $program, $before, $program->only(['name', 'period_start', 'period_end']));

        return $program->load('tiers');
    }

    public function setStatus(int $id, string $status, User $actor): IncentiveProgram
    {
        $program = $this->findProgram($id);
        $program->update(['status' => $status, 'updated_by' => $actor->id]);

        $this->auditLog->record($actor, 'status:'.$status, $program, null, ['status' => $status]);

        return $program;
    }

    /** Hitung ulang estimasi insentif semua karyawan aktif untuk program ini (aman dipanggil berkali-kali — upsert). */
    public function calculate(int $programId, User $actor): \Illuminate\Support\Collection
    {
        $program = $this->findProgram($programId);
        $bentesCategoryIds = EggCategory::where('code', 'BENTES')->pluck('id');

        $employees = User::where('role', 'karyawan')->where('is_active', true)->get();

        $totals = $employees->map(function (User $employee) use ($program, $bentesCategoryIds) {
            $sales = Sale::where('employee_id', $employee->id)
                ->whereDate('sale_date', '>=', $program->period_start)
                ->whereDate('sale_date', '<=', $program->period_end)
                ->with('items')
                ->get();

            $weightKg = 0.0;
            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    if (! $program->include_bentes && $bentesCategoryIds->contains($item->egg_category_id)) {
                        continue;
                    }
                    $weightKg += (float) $item->weight_kg;
                }
            }

            $omzet = $program->include_piutang
                ? (float) $sales->sum('total_amount')
                : (float) $sales->sum('paid_amount');

            return [
                'employee' => $employee,
                'weight_kg' => round($weightKg, 2),
                'omzet' => round($omzet, 2),
            ];
        });

        // Peringkat: urutkan dari kg terbesar untuk metode 'peringkat'.
        $ranked = $totals->sortByDesc('weight_kg')->values();

        $tiers = $program->tiers;

        return DB::transaction(function () use ($program, $totals, $ranked, $tiers, $actor) {
            $results = collect();

            foreach ($totals as $index => $entry) {
                /** @var User $employee */
                $employee = $entry['employee'];
                $rank = $ranked->search(fn ($r) => $r['employee']->id === $employee->id) + 1;

                $amount = match ($program->method) {
                    'berat' => $this->amountFromWeightTier($tiers, $entry['weight_kg']),
                    'persentase_omzet' => round($entry['omzet'] * (float) ($program->percentage_value ?? 0) / 100, 2),
                    'peringkat' => $this->amountFromRankTier($tiers, $rank, $entry['weight_kg']),
                    'target' => $entry['weight_kg'] >= (float) ($program->target_value_kg ?? PHP_INT_MAX)
                        ? (float) ($program->flat_bonus_amount ?? 0)
                        : 0.0,
                    default => 0.0,
                };

                $incentive = EmployeeIncentive::updateOrCreate(
                    ['incentive_program_id' => $program->id, 'employee_id' => $employee->id],
                    [
                        'uuid' => (string) Str::uuid(),
                        'total_weight_kg' => $entry['weight_kg'],
                        'total_omzet' => $entry['omzet'],
                        'rank' => $program->method === 'peringkat' ? $rank : null,
                        'calculated_amount' => $amount,
                        'status' => 'estimasi',
                    ]
                );

                $results->push($incentive->load('employee'));
            }

            $this->auditLog->record($actor, 'calculate', $program, null, ['employee_count' => $results->count()]);

            return $results;
        });
    }

    private function amountFromWeightTier(Collection $tiers, float $weightKg): float
    {
        $tier = $tiers->first(function (IncentiveTier $t) use ($weightKg) {
            $min = $t->min_value !== null ? (float) $t->min_value : -INF;
            $max = $t->max_value !== null ? (float) $t->max_value : INF;

            return $weightKg >= $min && $weightKg <= $max;
        });

        if (! $tier) {
            return 0.0;
        }

        return $tier->rate_type === 'per_kg'
            ? round($weightKg * (float) $tier->rate_value, 2)
            : (float) $tier->rate_value;
    }

    private function amountFromRankTier(Collection $tiers, int $rank, float $weightKg): float
    {
        $tier = $tiers->firstWhere('rank', $rank);

        if (! $tier) {
            return 0.0;
        }

        return $tier->rate_type === 'per_kg'
            ? round($weightKg * (float) $tier->rate_value, 2)
            : (float) $tier->rate_value;
    }

    public function approve(int $employeeIncentiveId, User $actor): EmployeeIncentive
    {
        $incentive = EmployeeIncentive::with('employee')->findOrFail($employeeIncentiveId);

        if ($incentive->status !== 'estimasi') {
            throw new \RuntimeException('Hanya insentif berstatus estimasi yang bisa disetujui.');
        }

        $incentive->update(['status' => 'disetujui', 'approved_by' => $actor->id, 'approved_at' => now()]);

        $this->notifier->send(
            $incentive->employee,
            'insentif',
            'Insentif disetujui',
            'Insentif Anda sebesar Rp'.number_format((float) $incentive->calculated_amount, 0, ',', '.').' sudah disetujui.',
            ['employee_incentive_id' => $incentive->id]
        );

        $this->auditLog->record($actor, 'approve', $incentive, null, ['status' => 'disetujui']);

        return $incentive;
    }

    public function markPaid(int $employeeIncentiveId, User $actor): EmployeeIncentive
    {
        $incentive = EmployeeIncentive::with('employee')->findOrFail($employeeIncentiveId);

        if ($incentive->status !== 'disetujui') {
            throw new \RuntimeException('Insentif harus disetujui dulu sebelum ditandai dibayar.');
        }

        $incentive->update(['status' => 'dibayar', 'paid_at' => now()]);

        $this->notifier->send(
            $incentive->employee,
            'insentif',
            'Insentif dibayarkan',
            'Insentif Anda sebesar Rp'.number_format((float) $incentive->calculated_amount, 0, ',', '.').' sudah dibayarkan.',
            ['employee_incentive_id' => $incentive->id]
        );

        $this->auditLog->record($actor, 'pay', $incentive, null, ['status' => 'dibayar']);

        return $incentive;
    }

    public function myIncentives(User $employee): Collection
    {
        return EmployeeIncentive::where('employee_id', $employee->id)->with('program')->orderByDesc('id')->get();
    }
}
