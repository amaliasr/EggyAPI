<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEggReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            // Wajib isi salah satu: egg_pickup_id (jalur lama — telur dibawa tidak laku)
            // ATAU sale_id (jalur baru — pelanggan sudah beli lalu komplain/kembalikan).
            // Divalidasi lebih lanjut di withValidator() supaya pesannya jelas.
            'egg_pickup_id' => ['nullable', 'integer', 'exists:egg_pickups,id'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'new_customer' => ['nullable', 'array'],
            'new_customer.name' => ['required_with:new_customer', 'string', 'max:150'],
            'new_customer.phone' => ['nullable', 'string', 'max:20'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],

            'egg_category_id' => ['required', 'integer', 'exists:egg_categories,id'],
            'return_date' => ['nullable', 'date'],
            'quantity_pcs' => ['required', 'integer', 'min:1'],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'crate_count' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasPickup = ! empty($this->input('egg_pickup_id'));
            $hasSale = ! empty($this->input('sale_id'));

            if (! $hasPickup && ! $hasSale) {
                $validator->errors()->add('egg_pickup_id', 'Wajib isi egg_pickup_id (retur stok pengambilan) atau sale_id (retur dari pelanggan).');
            }

            if ($hasPickup && $hasSale) {
                $validator->errors()->add('egg_pickup_id', 'Hanya boleh isi salah satu: egg_pickup_id atau sale_id, tidak keduanya.');
            }
        });
    }
}
