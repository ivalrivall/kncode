<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Skill;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_open_works(): void
    {
        $company = Company::factory()->create();
        Work::factory()->count(3)->open()->create(['company_id' => $company->id]);

        $response = $this->getJson('/api/works');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_only_open_works_are_listed_for_public(): void
    {
        $company = Company::factory()->create();
        Work::factory()->open()->create(['company_id' => $company->id]);
        Work::factory()->draft()->create(['company_id' => $company->id]);
        Work::factory()->inProgress()->create(['company_id' => $company->id]);
        Work::factory()->closed()->create(['company_id' => $company->id]);

        $response = $this->getJson('/api/works');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_open_works_can_be_searched(): void
    {
        $company = Company::factory()->create();
        Work::factory()->open()->create(['company_id' => $company->id, 'title' => 'Laravel Developer Needed']);
        Work::factory()->open()->create(['company_id' => $company->id, 'title' => 'React Developer Needed']);
        Work::factory()->open()->create(['company_id' => $company->id, 'title' => 'Python Developer Needed']);

        $response = $this->getJson('/api/works?search=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Developer Needed');
    }

    public function test_open_works_can_be_filtered_by_type(): void
    {
        $company = Company::factory()->create();
        Work::factory()->open()->create(['company_id' => $company->id, 'type' => 'fixed']);
        Work::factory()->open()->create(['company_id' => $company->id, 'type' => 'hourly']);

        $response = $this->getJson('/api/works?type=fixed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'fixed');
    }

    public function test_open_works_can_be_filtered_by_experience_level(): void
    {
        $company = Company::factory()->create();
        Work::factory()->open()->create(['company_id' => $company->id, 'experience_level' => 'expert']);
        Work::factory()->open()->create(['company_id' => $company->id, 'experience_level' => 'entry']);

        $response = $this->getJson('/api/works?experience_level=expert');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.experience_level', 'expert');
    }

    public function test_open_works_can_be_filtered_by_budget_range(): void
    {
        $company = Company::factory()->create();
        Work::factory()->open()->create(['company_id' => $company->id, 'budget_min' => 1000, 'budget_max' => 2000]);
        Work::factory()->open()->create(['company_id' => $company->id, 'budget_min' => 500, 'budget_max' => 5000]);

        $response = $this->getJson('/api/works?budget_min=1000&budget_max=3000');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_open_works_can_be_filtered_by_skills(): void
    {
        $company = Company::factory()->create();
        $skill1 = Skill::factory()->create();
        $skill2 = Skill::factory()->create();

        $work1 = Work::factory()->open()->create(['company_id' => $company->id]);
        $work1->skills()->attach($skill1);

        $work2 = Work::factory()->open()->create(['company_id' => $company->id]);
        $work2->skills()->attach($skill2);

        $response = $this->getJson('/api/works?skills[]=' . $skill1->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $work1->id);
    }

    public function test_open_works_can_be_sorted(): void
    {
        $company = Company::factory()->create();
        Work::factory()->open()->create(['company_id' => $company->id, 'title' => 'Alpha', 'budget_min' => 100]);
        Work::factory()->open()->create(['company_id' => $company->id, 'title' => 'Beta', 'budget_min' => 500]);

        // Sort by budget_min ascending
        $response = $this->getJson('/api/works?sort_by=budget_min&sort_order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Alpha')
            ->assertJsonPath('data.1.title', 'Beta');
    }

    public function test_open_works_pagination_works(): void
    {
        $company = Company::factory()->create();
        Work::factory()->count(20)->open()->create(['company_id' => $company->id]);

        $response = $this->getJson('/api/works?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_open_works_include_company_and_skills(): void
    {
        $company = Company::factory()->create();
        $skill = Skill::factory()->create();
        $work = Work::factory()->open()->create(['company_id' => $company->id]);
        $work->skills()->attach($skill);

        $response = $this->getJson('/api/works');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'company', 'skills'],
                ],
            ]);
    }
}
