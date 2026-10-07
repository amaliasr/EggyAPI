<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'uuid' => ['nullable', 'uuid'],
            'sale_date' => ['nullable', 'date'],
            'egg_pickup_id' => ['nullable', 'integer', 'exists:egg_pickups,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'new_customer' => ['nullable', 'array'],
            'new_customer.name' => ['required_with:new_customer', 'string', 'max:150'],
            'new_customer.phone' => ['nullable', 'string', 'max:20'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:tunai,transfer,qris,piutang'],
            'proof_photo_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.egg_category_id' => ['required', 'integer', 'exists:egg_categories,id'],
            'items.*.quantity_pcs' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.crate_count' => ['nullable', 'integer', 'min:0'],
            'items.*.price_per_kg' => ['nullable', 'numeric', 'min:0'],

            'payments' => ['nullable', 'array'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.method' => ['required_with:payments', 'in:tunai,transfer,qris'],
            'payments.*.payment_date' => ['nullable', 'date'],
            'payments.*.proof_photo_path' => ['nullable', 'string', 'max:255'],
            'payments.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
