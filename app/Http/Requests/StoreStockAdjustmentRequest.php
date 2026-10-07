<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAtasan() ?? false;
    }

    public function rules(): array
    {
        return [
            'egg_category_id' => ['required', 'integer', 'exists:egg_categories,id'],
            'delta_quantity_pcs' => ['required', 'integer'],
            'delta_weight_kg' => ['required', 'numeric'],
            'notes' => ['required', 'string', 'max:255'],
        ];
    }
}
