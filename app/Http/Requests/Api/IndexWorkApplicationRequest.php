<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexWorkApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'company';
    }

    /**
     * @return array<string, mixed[]>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'rejected', 'withdrawn'])],
            'sort_by' => ['nullable', Rule::in(['created_at', 'proposed_rate'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
