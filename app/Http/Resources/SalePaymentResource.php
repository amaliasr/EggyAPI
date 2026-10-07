<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_date' => $this->payment_date?->toIso8601String(),
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'proof_photo_path' => $this->proof_photo_path,
            'notes' => $this->notes,
        ];
    }
}
