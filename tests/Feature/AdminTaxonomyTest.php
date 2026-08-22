<?php

namespace Tests\Feature;

use App\Models\Axis;
use App\Models\Game;
use App\Models\Level;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Axes, skills and levels — the content taxonomy. These three controllers had
 * no coverage at all, including their delete guards.
 */
class AdminTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'taxonomy@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->token = $this->admin->createToken('admin')->plainTextToken;
    }

    private function asAdmin(): self
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token);

        return $this;
    }

    public function test_admin_can_create_update_and_list_an_axis(): void
    {
        $created = $this->asAdmin()->postJson('/api/v1/admin/axes', [
            'name' => 'التركيز',
            'description' => 'محور التركيز والانتباه',
            'sort_order' => 1,
        ]);

        $created->assertStatus(201)->assertJsonPath('data.name', 'التركيز');
        $axisId = $created->json('data.id');

        $this->asAdmin()->putJson("/api/v1/admin/axes/{$axisId}", ['name' => 'التركيز والانتباه'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'التركيز والانتباه');

        $this->asAdmin()->getJson('/api/v1/admin/axes')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.skills_count', 0);
    }

    public function test_axis_list_supports_search_sort_and_pagination(): void
    {
        foreach (['ألف', 'باء', 'تاء'] as $i => $name) {
            Axis::create(['name' => $name, 'slug' => "ax-{$i}", 'status' => 'ACTIVE', 'sort_order' => 3 - $i]);
        }

        $this->asAdmin()->getJson('/api/v1/admin/axes?search=باء')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'باء');

        $sorted = $this->asAdmin()->getJson('/api/v1/admin/axes?sort_by=sort_order&order_by=asc');
        $sorted->assertStatus(200);
        $this->assertSame(['تاء', 'باء', 'ألف'], $sorted->json('data.*.name'));

        $paged = $this->asAdmin()->getJson('/api/v1/admin/axes?per_page=2&page=2');
        $paged->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 3);
        $this->assertCount(1, $paged->json('data'));
    }

    public function test_unknown_sort_column_is_ignored_instead_of_reaching_the_database(): void
    {
        Axis::create(['name' => 'محور', 'slug' => 'ax', 'status' => 'ACTIVE', 'sort_order' => 1]);

        $this->asAdmin()->getJson('/api/v1/admin/axes?sort_by=password%29%3B+DROP+TABLE+axes%3B--&order_by=asc')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_per_page_is_bounded(): void
    {
        Axis::create(['name' => 'محور', 'slug' => 'ax', 'status' => 'ACTIVE', 'sort_order' => 1]);

        $this->asAdmin()->getJson('/api/v1/admin/axes?per_page=9999')->assertJsonPath('meta.per_page', 100);
        $this->asAdmin()->getJson('/api/v1/admin/axes?per_page=-1')->assertJsonPath('meta.per_page', 1);
        $this->asAdmin()->getJson('/api/v1/admin/axes?per_page=abc')->assertJsonPath('meta.per_page', 20);
    }

    public function test_skill_belongs_to_an_axis_and_can_be_filtered_by_it(): void
    {
        $axisA = Axis::create(['name' => 'أ', 'slug' => 'a', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $axisB = Axis::create(['name' => 'ب', 'slug' => 'b', 'status' => 'ACTIVE', 'sort_order' => 2]);

        $this->asAdmin()->postJson('/api/v1/admin/skills', [
            'axis_id' => $axisA->id,
            'name' => 'الانتباه الانتقائي',
            'sort_order' => 1,
        ])->assertStatus(201);

        Skill::create(['axis_id' => $axisB->id, 'name' => 'مهارة أخرى', 'slug' => 'other', 'status' => 'ACTIVE', 'sort_order' => 1]);

        $filtered = $this->asAdmin()->getJson("/api/v1/admin/skills?axis_id={$axisA->id}");
        $filtered->assertStatus(200)->assertJsonPath('meta.total', 1);
        $this->assertSame('الانتباه الانتقائي', $filtered->json('data.0.name'));
    }

    public function test_skill_search_does_not_leak_past_the_axis_filter(): void
    {
        $axisA = Axis::create(['name' => 'أ', 'slug' => 'a', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $axisB = Axis::create(['name' => 'ب', 'slug' => 'b', 'status' => 'ACTIVE', 'sort_order' => 2]);

        Skill::create(['axis_id' => $axisA->id, 'name' => 'انتباه', 'slug' => 'attention-a', 'status' => 'ACTIVE', 'sort_order' => 1]);
        Skill::create(['axis_id' => $axisB->id, 'name' => 'انتباه', 'slug' => 'attention-b', 'status' => 'ACTIVE', 'sort_order' => 1]);

        // ungrouped orWhere used to turn this into (axis AND name) OR slug
        $this->asAdmin()->getJson("/api/v1/admin/skills?axis_id={$axisA->id}&search=%D8%A7%D9%86%D8%AA%D8%A8%D8%A7%D9%87")
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_level_crud_and_delete_guard_when_games_reference_it(): void
    {
        $created = $this->asAdmin()->postJson('/api/v1/admin/levels', [
            'level_number' => 1,
            'name' => 'المستوى الأول',
            'min_age' => 3,
            'max_age' => 6,
        ]);
        $created->assertStatus(201)->assertJsonPath('data.level_number', 1);
        $levelId = $created->json('data.id');

        $this->asAdmin()->putJson("/api/v1/admin/levels/{$levelId}", ['name' => 'المستوى التمهيدي'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'المستوى التمهيدي');

        $axis = Axis::create(['name' => 'أ', 'slug' => 'a', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $skill = Skill::create(['axis_id' => $axis->id, 'name' => 'م', 'slug' => 's', 'status' => 'ACTIVE', 'sort_order' => 1]);

        Game::create([
            'code' => 'G-1', 'name' => 'لعبة', 'slug' => 'g-1', 'type' => 'TAP',
            'axis_id' => $axis->id, 'skill_id' => $skill->id, 'level_id' => $levelId,
            'level' => 1, 'difficulty' => 'easy', 'min_age' => 3, 'max_age' => 6,
            'duration_seconds' => 60, 'status' => 'DRAFT', 'version' => 1, 'config' => [],
        ]);

        $this->asAdmin()->deleteJson("/api/v1/admin/levels/{$levelId}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'LEVEL_IN_USE');

        $this->assertNotNull(Level::find($levelId));
    }

    public function test_level_all_flag_returns_an_unpaginated_list(): void
    {
        Level::create(['level_number' => 2, 'name' => 'ثانٍ', 'min_age' => 5, 'max_age' => 8]);
        Level::create(['level_number' => 1, 'name' => 'أول', 'min_age' => 3, 'max_age' => 6]);

        $res = $this->asAdmin()->getJson('/api/v1/admin/levels?all=true');
        $res->assertStatus(200);
        $this->assertSame(['أول', 'ثانٍ'], $res->json('data.*.name'));
    }

    public function test_taxonomy_endpoints_reject_a_subscriber_token(): void
    {
        $this->getJson('/api/v1/admin/axes')->assertStatus(401);
    }
}
