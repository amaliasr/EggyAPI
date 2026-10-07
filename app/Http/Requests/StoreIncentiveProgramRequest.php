<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncentiveProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAtasan() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'method' => ['required', 'in:berat,persentase_omzet,peringkat,target'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'include_bentes' => ['nullable', 'boolean'],
            'include_piutang' => ['nullable', 'boolean'],
            'percentage_value' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:method,persentase_omzet'],
            'target_value_kg' => ['nullable', 'numeric', 'min:0', 'required_if:method,target'],
            'flat_bonus_amount' => ['nullable', 'numeric', 'min:0', 'required_if:method,target'],

            'tiers' => ['nullable', 'array'],
            'tiers.*.tier_order' => ['nullable', 'integer', 'min:1'],
            'tiers.*.min_value' => ['nullable', 'numeric'],
            'tiers.*.max_value' => ['nullable', 'numeric'],
            'tiers.*.rank' => ['nullable', 'integer', 'min:1'],
            'tiers.*.rate_type' => ['required_with:tiers', 'in:per_kg,nominal_tetap'],
            'tiers.*.rate_value' => ['required_with:tiers', 'numeric', 'min:0'],
        ];
    }
}
