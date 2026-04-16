<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Freelance;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Portfolio>
 */
final class PortfolioFactory extends Factory
{
    protected $model = Portfolio::class;

    public function definition(): array
    {
        return [
            'freelancer_id' => Freelance::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'project_url' => fake()->url(),
            'project_image_url' => fake()->imageUrl(),
        ];
    }
}
