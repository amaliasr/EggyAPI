<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'reference_type' => ['required', 'string', 'max:50'],
            'reference_id' => ['required', 'integer'],
            'reason' => ['required', 'string'],
            'proposed_changes' => ['required', 'array'],
        ];
    }
}
