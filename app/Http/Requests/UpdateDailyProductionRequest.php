<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'farm_source_id' => ['nullable', 'integer', 'exists:farm_sources,id'],
        ];
    }
}
