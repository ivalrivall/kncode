<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Freelance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Freelance>
 */
final class FreelanceFactory extends Factory
{
    protected $model = Freelance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fullname' => fake()->name(),
            'headline' => fake()->sentence(),
            'bio' => fake()->paragraph(),
            'experience_years' => fake()->numberBetween(1, 15),
            'hourly_rate' => fake()->randomFloat(2, 10, 200),
            'availability' => fake()->randomElement(['available', 'unavailable']),
            'location' => fake()->city(),
            'rating_avg' => fake()->randomFloat(2, 1, 5),
            'total_reviews' => fake()->numberBetween(0, 50),
        ];
    }
}
