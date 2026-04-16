<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Skill;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_post_a_work(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $skills = Skill::factory()->count(3)->create();

        $payload = [
            'title' => 'Senior Laravel Developer',
            'description' => 'Looking for an expert developer.',
            'budget_min' => 1000,
            'budget_max' => 5000,
            'type' => 'fixed',
            'experience_level' => 'expert',
            'deadline_date' => now()->addMonth()->toDateString(),
            'skills' => $skills->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/works', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonCount(3, 'data.skills');

        $this->assertDatabaseHas('works', [
            'company_id' => $company->id,
            'title' => $payload['title'],
        ]);

        foreach ($skills as $skill) {
            $this->assertDatabaseHas('work_skills', [
                'skill_id' => $skill->id,
            ]);
        }
    }

    public function test_freelance_cannot_post_a_work(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);

        $payload = [
            'title' => 'Invalid Post',
            'type' => 'fixed',
            'experience_level' => 'entry',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/works', $payload);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_post_a_work(): void
    {
        $response = $this->postJson('/api/works', []);

        $response->assertUnauthorized();
    }

    public function test_it_validates_required_fields(): void
    {
        $user = User::factory()->create(['role' => 'company']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/works', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'type', 'experience_level']);
    }

    public function test_it_validates_budget_ranges(): void
    {
        $user = User::factory()->create(['role' => 'company']);

        $payload = [
            'title' => 'Invalid Budget',
            'type' => 'fixed',
            'experience_level' => 'entry',
            'budget_min' => 1000,
            'budget_max' => 500, // max < min
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/works', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['budget_max']);
    }
}
