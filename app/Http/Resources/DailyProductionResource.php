<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyProductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'production_date' => $this->production_date?->toDateString(),
            'farm_source_id' => $this->farm_source_id,
            'farm_source_name' => $this->whenLoaded('farmSource', fn () => $this->farmSource?->name),
            'recorded_by' => $this->recorded_by,
            'recorded_by_name' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy?->name),
            'total_qty_pcs' => $this->total_qty_pcs,
            'total_weight_kg' => (float) $this->total_weight_kg,
            'total_crates' => $this->total_crates,
            'notes' => $this->notes,
            'photo_path' => $this->photo_path,
            'is_locked' => $this->is_locked,
            'sync_status' => $this->sync_status,
            'items' => DailyProductionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
