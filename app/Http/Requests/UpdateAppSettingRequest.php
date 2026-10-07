<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAtasan() ?? false;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
