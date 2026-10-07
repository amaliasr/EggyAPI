<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeIncentiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'incentive_program_id' => $this->incentive_program_id,
            'program_name' => $this->whenLoaded('program', fn () => $this->program?->name),
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'total_weight_kg' => (float) $this->total_weight_kg,
            'total_omzet' => (float) $this->total_omzet,
            'rank' => $this->rank,
            'calculated_amount' => (float) $this->calculated_amount,
            'status' => $this->status,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
