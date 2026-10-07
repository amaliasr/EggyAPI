<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EggReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'egg_pickup_id' => $this->egg_pickup_id,
            'pickup_code' => $this->whenLoaded('pickup', fn () => $this->pickup?->pickup_code),
            'sale_id' => $this->sale_id,
            'sale_transaction_no' => $this->whenLoaded('sale', fn () => $this->sale?->transaction_no),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ] : null),
            'egg_category_id' => $this->egg_category_id,
            'egg_category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'return_date' => $this->return_date?->toIso8601String(),
            'quantity_pcs' => $this->quantity_pcs,
            'weight_kg' => (float) $this->weight_kg,
            'crate_count' => $this->crate_count,
            'refund_amount' => $this->refund_amount !== null ? (float) $this->refund_amount : null,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
