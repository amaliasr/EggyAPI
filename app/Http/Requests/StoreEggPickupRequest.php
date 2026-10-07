<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEggPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'uuid' => ['nullable', 'uuid'],
            'pickup_date' => ['nullable', 'date'],
            'destination_area' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.egg_category_id' => ['required', 'integer', 'exists:egg_categories,id'],
            'items.*.quantity_pcs' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.crate_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
