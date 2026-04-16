<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Freelance;
use App\Models\Work;
use App\Models\WorkApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkApplication>
 */
final class WorkApplicationFactory extends Factory
{
    protected $model = WorkApplication::class;

    public function definition(): array
    {
        return [
            'work_id' => Work::factory(),
            'freelancer_id' => Freelance::factory(),
            'cover_letter' => fake()->paragraph(),
            'proposed_rate' => fake()->randomFloat(2, 10, 200),
            'status' => 'pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
