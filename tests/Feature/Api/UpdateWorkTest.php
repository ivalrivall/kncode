<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Freelance;
use App\Models\Skill;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_update_their_own_work(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create(['company_id' => $company->id, 'title' => 'Original Title']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_company_can_update_work_status_from_draft_to_open(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create(['company_id' => $company->id, 'status' => 'draft']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'status' => 'open',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'status' => 'open',
        ]);
    }

    public function test_company_can_update_multiple_fields_at_once(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create([
            'company_id' => $company->id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'budget_min' => 1000,
            'budget_max' => 5000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'title' => 'New Title',
                'description' => 'New description',
                'budget_min' => 2000,
                'budget_max' => 8000,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'New Title')
            ->assertJsonPath('data.description', 'New description')
            ->assertJsonPath('data.budget_min', '2000.00')
            ->assertJsonPath('data.budget_max', '8000.00');
    }

    public function test_company_can_update_skills(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create(['company_id' => $company->id]);
        $skill1 = Skill::factory()->create();
        $skill2 = Skill::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'skills' => [$skill1->id, $skill2->id],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('work_skills', [
            'work_id' => $work->id,
            'skill_id' => $skill1->id,
        ]);

        $this->assertDatabaseHas('work_skills', [
            'work_id' => $work->id,
            'skill_id' => $skill2->id,
        ]);
    }

    public function test_company_cannot_update_another_companys_work(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);
        $work = Work::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You do not own this work.');
    }

    public function test_freelance_cannot_update_a_work(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        Freelance::factory()->create(['user_id' => $user->id]);
        $company = Company::factory()->create();
        $work = Work::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_update_a_work(): void
    {
        $company = Company::factory()->create();
        $work = Work::factory()->create(['company_id' => $company->id]);

        $response = $this->putJson("/api/works/{$work->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertUnauthorized();
    }

    public function test_updating_nonexistent_work_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/works/99999', [
                'title' => 'Does not exist',
            ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Work not found.');
    }

    public function test_update_validates_invalid_type(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'type' => 'invalid_type',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_update_validates_invalid_status(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'status' => 'invalid_status',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_validates_budget_range(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'budget_min' => 5000,
                'budget_max' => 1000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['budget_max']);
    }

    public function test_company_without_profile_cannot_update_work(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        // No company profile created
        $otherCompany = Company::factory()->create();
        $work = Work::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/works/{$work->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'User does not have a company profile.');
    }
}
