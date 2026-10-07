<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'transaction_no' => $this->transaction_no,
            'sale_date' => $this->sale_date?->toIso8601String(),
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'egg_pickup_id' => $this->egg_pickup_id,
            'customer_id' => $this->customer_id,
            // customer_name/customer_phone: snapshot teks (selalu ada, walk-in maupun tercatat).
            // customer di bawah ini hanya terisi kalau transaksi terhubung ke tabel customers
            // dan relasinya di-load (whenLoaded) — dipakai frontend untuk link ke profil pelanggan.
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ] : null),
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'notes' => $this->notes,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'payments' => SalePaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
