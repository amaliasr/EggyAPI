<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorrectionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'requested_by' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'reason' => $this->reason,
            'proposed_changes' => $this->proposed_changes,
            'status' => $this->status,
            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
