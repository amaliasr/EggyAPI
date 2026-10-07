<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\IncentiveProgram;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Endpoint laporan bersifat agregasi/read-only — tidak menulis data apa pun.
 * Semua query dibatasi rentang tanggal wajib supaya tidak menarik seluruh
 * tabel transaksi sekaligus di data yang sudah besar.
 */
class ReportService
{
    public function productionSummary(array $filters, ?User $scopeToUser = null): array
    {
        $query = DailyProduction::query()
            ->whereDate('production_date', '>=', $filters['date_from'])
            ->whereDate('production_date', '<=', $filters['date_to']);

        if ($scopeToUser) {
            $query->where('recorded_by', $scopeToUser->id);
        }

        $productions = $query->with('items.category')->get();

        $byCategory = $productions->flatMap->items
            ->groupBy(fn ($item) => $item->category?->name ?? 'Lainnya')
            ->map(fn (Collection $items) => [
                'quantity_pcs' => $items->sum('quantity_pcs'),
                'weight_kg' => round((float) $items->sum('weight_kg'), 2),
            ]);

        $byDate = $productions->groupBy(fn ($p) => $p->production_date->toDateString())
            ->map(fn (Collection $items) => [
                'quantity_pcs' => $items->sum('total_qty_pcs'),
                'weight_kg' => round((float) $items->sum('total_weight_kg'), 2),
            ])
            ->sortKeys();

        return [
            'total_quantity_pcs' => $productions->sum('total_qty_pcs'),
            'total_weight_kg' => round((float) $productions->sum('total_weight_kg'), 2),
            'by_category' => $byCategory,
            'by_date' => $byDate,
        ];
    }

    public function salesSummary(array $filters, ?User $scopeToUser = null): array
    {
        $query = Sale::query()
            ->whereDate('sale_date', '>=', $filters['date_from'])
            ->whereDate('sale_date', '<=', $filters['date_to']);

        if ($scopeToUser) {
            $query->where('employee_id', $scopeToUser->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        $sales = $query->with(['items.category', 'employee'])->get();

        $byEmployee = $sales->groupBy(fn ($s) => $s->employee?->name ?? 'Tidak diketahui')
            ->map(fn (Collection $items) => [
                'transaction_count' => $items->count(),
                'total_amount' => round((float) $items->sum('total_amount'), 2),
                'weight_kg' => round((float) $items->flatMap->items->sum('weight_kg'), 2),
            ]);

        $byCategory = $sales->flatMap->items
            ->groupBy(fn ($item) => $item->category?->name ?? 'Lainnya')
            ->map(fn (Collection $items) => [
                'weight_kg' => round((float) $items->sum('weight_kg'), 2),
                'subtotal' => round((float) $items->sum('subtotal'), 2),
            ]);

        return [
            'transaction_count' => $sales->count(),
            'total_amount' => round((float) $sales->sum('total_amount'), 2),
            'paid_amount' => round((float) $sales->sum('paid_amount'), 2),
            'remaining_amount' => round((float) $sales->sum('remaining_amount'), 2),
            'by_employee' => $byEmployee,
            'by_category' => $byCategory,
        ];
    }

    public function incentiveSummary(int $programId): array
    {
        $program = IncentiveProgram::with('employeeIncentives.employee')->findOrFail($programId);

        return [
            'program_id' => $program->id,
            'program_name' => $program->name,
            'method' => $program->method,
            'status' => $program->status,
            'total_calculated_amount' => round((float) $program->employeeIncentives->sum('calculated_amount'), 2),
            'employee_count' => $program->employeeIncentives->count(),
            'employees' => $program->employeeIncentives->map(fn ($ei) => [
                'employee_name' => $ei->employee?->name,
                'total_weight_kg' => (float) $ei->total_weight_kg,
                'total_omzet' => (float) $ei->total_omzet,
                'rank' => $ei->rank,
                'calculated_amount' => (float) $ei->calculated_amount,
                'status' => $ei->status,
            ]),
        ];
    }
}
