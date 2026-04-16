<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Skill;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyWorkIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_list_their_own_works(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        Work::factory()->count(3)->draft()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_company_can_see_works_with_any_status(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        Work::factory()->draft()->create(['company_id' => $company->id]);
        Work::factory()->open()->create(['company_id' => $company->id]);
        Work::factory()->inProgress()->create(['company_id' => $company->id]);
        Work::factory()->closed()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works');

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_company_cannot_see_other_companies_works(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);

        Work::factory()->count(3)->open()->create(['company_id' => $company->id]);
        Work::factory()->count(5)->open()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_company_can_filter_works_by_status(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        Work::factory()->count(2)->draft()->create(['company_id' => $company->id]);
        Work::factory()->count(3)->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_company_can_search_their_works(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        Work::factory()->draft()->create(['company_id' => $company->id, 'title' => 'Laravel Project']);
        Work::factory()->draft()->create(['company_id' => $company->id, 'title' => 'React Project']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works?search=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Project');
    }

    public function test_company_can_filter_by_type(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        Work::factory()->draft()->create(['company_id' => $company->id, 'type' => 'fixed']);
        Work::factory()->draft()->create(['company_id' => $company->id, 'type' => 'hourly']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works?type=fixed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'fixed');
    }

    public function test_company_can_sort_their_works(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);

        Work::factory()->draft()->create(['company_id' => $company->id, 'title' => 'Alpha', 'budget_min' => 100]);
        Work::factory()->draft()->create(['company_id' => $company->id, 'title' => 'Beta', 'budget_min' => 500]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works?sort_by=budget_min&sort_order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Alpha')
            ->assertJsonPath('data.1.title', 'Beta');
    }

    public function test_company_works_pagination_works(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $user->id]);
        Work::factory()->count(20)->draft()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_freelance_cannot_access_company_works(): void
    {
        $user = User::factory()->create(['role' => 'freelance']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/company/works');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_company_works(): void
    {
        $response = $this->getJson('/api/company/works');

        $response->assertUnauthorized();
    }
}
