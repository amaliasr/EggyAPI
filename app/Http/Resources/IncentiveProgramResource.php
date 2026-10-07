<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncentiveProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'method' => $this->method,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'include_bentes' => $this->include_bentes,
            'include_piutang' => $this->include_piutang,
            'percentage_value' => $this->percentage_value !== null ? (float) $this->percentage_value : null,
            'target_value_kg' => $this->target_value_kg !== null ? (float) $this->target_value_kg : null,
            'flat_bonus_amount' => $this->flat_bonus_amount !== null ? (float) $this->flat_bonus_amount : null,
            'status' => $this->status,
            'tiers' => IncentiveTierResource::collection($this->whenLoaded('tiers')),
            'employee_incentives' => EmployeeIncentiveResource::collection($this->whenLoaded('employeeIncentives')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
