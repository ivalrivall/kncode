<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Freelance;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubmitWorkApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_freelancer_can_apply_to_open_work(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        $freelance = Freelance::factory()->create(['user_id' => $user->id]);
        $company = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $payload = [
            'cover_letter' => 'I am very interested in this project.',
            'proposed_rate' => 75.00,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/works/{$work->id}/applications", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.cover_letter', $payload['cover_letter'])
            ->assertJsonPath('data.proposed_rate', '75.00')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.work_id', $work->id)
            ->assertJsonPath('data.freelancer_id', $freelance->id);

        $this->assertDatabaseHas('work_applications', [
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
            'status' => 'pending',
        ]);
    }

    public function test_application_defaults_to_pending_status(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        Freelance::factory()->create(['user_id' => $user->id]);
        $company = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/works/{$work->id}/applications", [
                'cover_letter' => 'Test',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('work_applications', [
            'work_id' => $work->id,
            'status' => 'pending',
        ]);
    }

    public function test_company_cannot_apply_to_work(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $otherCompany = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/works/{$work->id}/applications", [
                'cover_letter' => 'Test',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_apply_to_non_open_work(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        Freelance::factory()->create(['user_id' => $user->id]);
        $company = Company::factory()->create();

        foreach (['draft', 'in_progress', 'closed', 'cancelled'] as $status) {
            $work = Work::factory()->create(['company_id' => $company->id, 'status' => $status]);

            $response = $this->actingAs($user, 'sanctum')
                ->postJson("/api/works/{$work->id}/applications", [
                    'cover_letter' => 'Test',
                ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'This work is not open for applications.');
        }
    }

    public function test_freelancer_cannot_apply_twice_to_same_work(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        $freelance = Freelance::factory()->create(['user_id' => $user->id]);
        $company = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        // First application
        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        // Second application attempt
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/works/{$work->id}/applications", [
                'cover_letter' => 'Another attempt',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'You have already applied to this work.');
    }

    public function test_freelancer_without_profile_cannot_apply(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        // No Freelance profile created
        $company = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/works/{$work->id}/applications", [
                'cover_letter' => 'Test',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'User does not have a freelancer profile.');
    }

    public function test_unauthenticated_user_cannot_apply(): void
    {
        $company = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->postJson("/api/works/{$work->id}/applications", [
            'cover_letter' => 'Test',
        ]);

        $response->assertUnauthorized();
    }

    public function test_application_validates_proposed_rate(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        Freelance::factory()->create(['user_id' => $user->id]);
        $company = Company::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/works/{$work->id}/applications", [
                'proposed_rate' => -10,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['proposed_rate']);
    }

    public function test_applying_to_nonexistent_work_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);
        Freelance::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/works/99999/applications', [
                'cover_letter' => 'Test',
            ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Work not found.');
    }
}
