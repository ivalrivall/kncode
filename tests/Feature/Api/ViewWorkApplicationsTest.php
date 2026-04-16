<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Freelance;
use App\Models\Portfolio;
use App\Models\Skill;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ViewWorkApplicationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // INDEX (List) Tests
    // ==========================================

    public function test_company_can_view_applications_for_their_work(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelanceUser = User::factory()->create(['role' => 'freelance']);
        $freelance = Freelance::factory()->create(['user_id' => $freelanceUser->id]);
        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.work_id', $work->id)
            ->assertJsonPath('data.0.freelancer_id', $freelance->id)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'work_id', 'freelancer_id', 'cover_letter', 'proposed_rate', 'status', 'created_at', 'updated_at', 'freelance'],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_applications_include_freelancer_profile_with_skills_and_portfolios(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelanceUser = User::factory()->create(['role' => 'freelance']);
        $freelance = Freelance::factory()->create(['user_id' => $freelanceUser->id]);
        $skill = Skill::factory()->create();
        $freelance->skills()->attach($skill->id, ['level' => 'expert']);
        Portfolio::factory()->create(['freelancer_id' => $freelance->id]);

        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications");

        $response->assertOk();

        $freelanceData = $response->json('data.0.freelance');
        $this->assertEquals($freelance->id, $freelanceData['id']);
        $this->assertEquals($freelance->fullname, $freelanceData['fullname']);
        $this->assertEquals($freelance->headline, $freelanceData['headline']);
        $this->assertEquals($freelance->bio, $freelanceData['bio']);
        $this->assertEquals($freelance->experience_years, $freelanceData['experience_years']);
        $this->assertEquals($freelance->location, $freelanceData['location']);

        // Assert skills are included with pivot level
        $this->assertCount(1, $freelanceData['skills']);
        $this->assertEquals($skill->id, $freelanceData['skills'][0]['id']);
        $this->assertEquals($skill->name, $freelanceData['skills'][0]['name']);
        $this->assertEquals('expert', $freelanceData['skills'][0]['level']);

        // Assert portfolios are included
        $this->assertCount(1, $freelanceData['portfolios']);
        $this->assertArrayHasKey('title', $freelanceData['portfolios'][0]);
        $this->assertArrayHasKey('description', $freelanceData['portfolios'][0]);
    }

    public function test_freelancer_cannot_view_applications(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $companyUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelanceUser = User::factory()->create(['role' => 'freelance']);

        $response = $this->actingAs($freelanceUser, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_applications(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $companyUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->getJson("/api/works/{$work->id}/applications");

        $response->assertUnauthorized();
    }

    public function test_company_cannot_view_applications_for_work_they_dont_own(): void
    {
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $otherCompany->id]);

        $user = User::factory()->create(['role' => 'company']);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications");

        $response->assertForbidden()
            ->assertJsonPath('message', 'You do not own this work.');
    }

    public function test_viewing_applications_for_nonexistent_work_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/works/99999/applications');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Work not found.');
    }

    public function test_company_without_company_profile_cannot_view_applications(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $companyUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $userWithoutCompany = User::factory()->create(['role' => 'company']);

        $response = $this->actingAs($userWithoutCompany, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications");

        $response->assertForbidden()
            ->assertJsonPath('message', 'You do not have a company profile.');
    }

    public function test_company_can_filter_applications_by_status(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelance1 = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
        $freelance2 = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);

        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance1->id,
            'status' => 'pending',
        ]);
        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance2->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications?status=pending");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_company_can_sort_applications(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelance1 = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
        $freelance2 = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);

        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance1->id,
            'proposed_rate' => 100.00,
        ]);
        WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance2->id,
            'proposed_rate' => 50.00,
        ]);

        // Sort by proposed_rate ascending
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications?sort_by=proposed_rate&sort_order=asc");

        $response->assertOk();
        $this->assertEquals('50.00', $response->json('data.0.proposed_rate'));
        $this->assertEquals('100.00', $response->json('data.1.proposed_rate'));
    }

    public function test_applications_are_paginated(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        // Create 5 applications
        for ($i = 0; $i < 5; $i++) {
            $freelance = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
            WorkApplication::factory()->create([
                'work_id' => $work->id,
                'freelancer_id' => $freelance->id,
            ]);
        }

        // Request 2 per page
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications?per_page=2");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1);
    }

    // ==========================================
    // SHOW (Single Detail) Tests
    // ==========================================

    public function test_company_can_view_single_application_detail(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelanceUser = User::factory()->create(['role' => 'freelance']);
        $freelance = Freelance::factory()->create(['user_id' => $freelanceUser->id]);
        $application = WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.work_id', $work->id)
            ->assertJsonPath('data.freelancer_id', $freelance->id)
            ->assertJsonPath('data.cover_letter', $application->cover_letter)
            ->assertJsonPath('data.proposed_rate', $application->proposed_rate)
            ->assertJsonPath('data.status', $application->status)
            ->assertJsonStructure([
                'data' => [
                    'id', 'work_id', 'freelancer_id', 'cover_letter', 'proposed_rate',
                    'status', 'created_at', 'updated_at', 'freelance',
                ],
            ]);
    }

    public function test_single_application_includes_freelancer_skills_and_portfolios(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelanceUser = User::factory()->create(['role' => 'freelance']);
        $freelance = Freelance::factory()->create(['user_id' => $freelanceUser->id]);
        $skill1 = Skill::factory()->create();
        $skill2 = Skill::factory()->create();
        $freelance->skills()->attach($skill1->id, ['level' => 'beginner']);
        $freelance->skills()->attach($skill2->id, ['level' => 'expert']);
        $portfolio = Portfolio::factory()->create(['freelancer_id' => $freelance->id]);

        $application = WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications/{$application->id}");

        $response->assertOk();

        $freelanceData = $response->json('data.freelance');
        $this->assertCount(2, $freelanceData['skills']);
        $this->assertCount(1, $freelanceData['portfolios']);

        // Verify skill levels from pivot
        $skillLevels = collect($freelanceData['skills'])->pluck('level')->toArray();
        $this->assertContains('beginner', $skillLevels);
        $this->assertContains('expert', $skillLevels);

        // Verify portfolio data
        $this->assertEquals($portfolio->title, $freelanceData['portfolios'][0]['title']);
    }

    public function test_company_cannot_view_application_detail_for_work_they_dont_own(): void
    {
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $otherCompany->id]);

        $freelance = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
        $application = WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $user = User::factory()->create(['role' => 'company']);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications/{$application->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'You do not own this work.');
    }

    public function test_viewing_nonexistent_application_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications/99999");

        $response->assertNotFound()
            ->assertJsonPath('message', 'Application not found.');
    }

    public function test_viewing_application_for_nonexistent_work_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/works/99999/applications/1');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Work not found.');
    }

    public function test_application_belonging_to_different_work_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $work1 = Work::factory()->open()->create(['company_id' => $company->id]);
        $work2 = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelance = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
        $application = WorkApplication::factory()->create([
            'work_id' => $work1->id,
            'freelancer_id' => $freelance->id,
        ]);

        // Try to access application from work1 using work2's URL
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/works/{$work2->id}/applications/{$application->id}");

        $response->assertNotFound()
            ->assertJsonPath('message', 'Application not found.');
    }

    public function test_freelancer_cannot_view_application_detail(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $companyUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelance = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
        $application = WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $otherFreelanceUser = User::factory()->create(['role' => 'freelance']);

        $response = $this->actingAs($otherFreelanceUser, 'sanctum')
            ->getJson("/api/works/{$work->id}/applications/{$application->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_application_detail(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $companyUser->id]);
        $work = Work::factory()->open()->create(['company_id' => $company->id]);

        $freelance = Freelance::factory()->create(['user_id' => User::factory()->create(['role' => 'freelance'])->id]);
        $application = WorkApplication::factory()->create([
            'work_id' => $work->id,
            'freelancer_id' => $freelance->id,
        ]);

        $response = $this->getJson("/api/works/{$work->id}/applications/{$application->id}");

        $response->assertUnauthorized();
    }
}
