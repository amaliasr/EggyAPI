<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyProductionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'egg_category_id' => $this->egg_category_id,
            'egg_category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'quantity_pcs' => $this->quantity_pcs,
            'weight_kg' => (float) $this->weight_kg,
            'crate_count' => $this->crate_count,
            'estimated_weight_kg' => $this->estimated_weight_kg !== null ? (float) $this->estimated_weight_kg : null,
            'weight_variance_flagged' => $this->weight_variance_flagged,
            'notes' => $this->notes,
        ];
    }
}
