<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'uuid' => ['nullable', 'uuid'],
            'production_date' => ['required', 'date'],
            'farm_source_id' => ['nullable', 'integer', 'exists:farm_sources,id'],
            'notes' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.egg_category_id' => ['required', 'integer', 'exists:egg_categories,id'],
            'items.*.quantity_pcs' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.crate_count' => ['nullable', 'integer', 'min:0'],
            'items.*.estimated_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
