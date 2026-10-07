<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EggPickupItemResource extends JsonResource
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
            'weight_sold_kg' => (float) $this->weight_sold_kg,
            'weight_returned_kg' => (float) $this->weight_returned_kg,
            'weight_remaining_kg' => $this->remainingWeightKg(),
        ];
    }
}
