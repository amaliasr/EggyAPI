<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEggPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAtasan() ?? false;
    }

    public function rules(): array
    {
        return [
            'price_per_kg' => ['sometimes', 'numeric', 'min:0'],
            'effective_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
