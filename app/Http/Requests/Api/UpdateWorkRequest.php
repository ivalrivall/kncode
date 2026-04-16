<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'company';
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'budget_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'budget_max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'type' => ['sometimes', Rule::in(['fixed', 'hourly'])],
            'experience_level' => ['sometimes', Rule::in(['entry', 'intermediate', 'expert'])],
            'deadline_date' => ['sometimes', 'nullable', 'date', 'after:today'],
            'status' => ['sometimes', Rule::in(['draft', 'open', 'closed', 'cancelled'])],
            'skills' => ['sometimes', 'nullable', 'array'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')],
        ];
    }
}
