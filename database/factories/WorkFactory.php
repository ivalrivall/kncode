<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Work>
 */
final class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'budget_min' => fake()->randomFloat(2, 100, 1000),
            'budget_max' => fake()->randomFloat(2, 1000, 5000),
            'type' => fake()->randomElement(['fixed', 'hourly']),
            'experience_level' => fake()->randomElement(['entry', 'intermediate', 'expert']),
            'status' => 'draft',
            'deadline_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
