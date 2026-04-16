<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'company';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'type' => ['required', Rule::in(['fixed', 'hourly'])],
            'experience_level' => ['required', Rule::in(['entry', 'intermediate', 'expert'])],
            'deadline_date' => ['nullable', 'date', 'after:today'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')],
        ];
    }
}
