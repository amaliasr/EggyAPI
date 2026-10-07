<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EggPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'egg_category_id' => $this->egg_category_id,
            'egg_category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'price_per_kg' => (float) $this->price_per_kg,
            'effective_date' => $this->effective_date?->toDateString(),
            'status' => $this->status,
            'set_by_name' => $this->whenLoaded('setBy', fn () => $this->setBy?->name),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
