<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAtasan() ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:disetujui,ditolak'],
            'review_notes' => ['nullable', 'string'],
        ];
    }
}
