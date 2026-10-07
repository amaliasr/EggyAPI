<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'target_weight_kg' => (float) $this->target_weight_kg,
            'target_omzet' => $this->target_omzet !== null ? (float) $this->target_omzet : null,
            'bonus_amount' => $this->bonus_amount !== null ? (float) $this->bonus_amount : null,
            'progress' => $this->when(isset($this->progress), fn () => $this->progress),
        ];
    }
}
