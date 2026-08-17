<?php

namespace Tests\Feature;

use App\Enums\CurriculumStatus;
use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Models\Axis;
use App\Models\Curriculum;
use App\Models\Game;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCurriculumTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $axis = Axis::create(['name' => 'المحور', 'slug' => 'axis', 'status' => 'ACTIVE']);
        $skill = Skill::create(['axis_id' => $axis->id, 'name' => 'المهارة', 'slug' => 'skill', 'status' => 'ACTIVE']);

        $this->game = Game::create([
            'code' => 'GAME-PUB',
            'name' => 'لعبة منشورة',
            'slug' => 'pub-game',
            'type' => GameType::TAP,
            'axis_id' => $axis->id,
            'skill_id' => $skill->id,
            'status' => GameStatus::PUBLISHED,
            'published_at' => now(),
            'config' => [
                'attempts' => 10,
                'success_threshold' => 0.8,
                'time_limit_seconds' => 60,
            ],
        ]);
    }

    public function test_admin_can_build_and_publish_curriculum(): void
    {
        $token = $this->admin->createToken('admin')->plainTextToken;

        // 1. Create Curriculum
        $currRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/curriculums', [
                'code' => 'CURR-TEST-BUILD',
                'name' => 'منهج الاختبار',
            ]);
        $currRes->assertStatus(201);
        $currId = $currRes->json('data.id');

        // 2. Add Month
        $monthRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/curriculums/{$currId}/months", [
                'month_number' => 1,
                'name' => 'الشهر الأول',
            ]);
        $monthRes->assertStatus(201);
        $monthId = $monthRes->json('data.id');

        // 3. Add Week
        $weekRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/curriculums/months/{$monthId}/weeks", [
                'week_number' => 1,
                'name' => 'الأسبوع الأول',
            ]);
        $weekRes->assertStatus(201);
        $weekId = $weekRes->json('data.id');

        // 4. Add Day
        $dayRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/curriculums/weeks/{$weekId}/days", [
                'day_number' => 1,
                'name' => 'اليوم الأول',
            ]);
        $dayRes->assertStatus(201);
        $dayId = $dayRes->json('data.id');

        // 5. Attach Game
        $attachRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/curriculums/days/{$dayId}/games", [
                'game_id' => $this->game->id,
                'sort_order' => 1,
                'is_required' => true,
            ]);
        $attachRes->assertStatus(201);

        // 6. Publish Curriculum
        $publishRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/curriculums/{$currId}/publish");
        $publishRes->assertStatus(200)
            ->assertJsonPath('data.status', 'PUBLISHED');
    }
}
