<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'freelance';
    }

    public function rules(): array
    {
        return [
            'cover_letter' => ['nullable', 'string'],
            'proposed_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposed_rate.min' => 'The proposed rate must be at least 0.',
        ];
    }
}
