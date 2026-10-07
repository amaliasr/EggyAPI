<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncentiveProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAtasan() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'period_start' => ['sometimes', 'date'],
            'period_end' => ['sometimes', 'date', 'after_or_equal:period_start'],
            'include_bentes' => ['nullable', 'boolean'],
            'include_piutang' => ['nullable', 'boolean'],
            'percentage_value' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_value_kg' => ['nullable', 'numeric', 'min:0'],
            'flat_bonus_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
