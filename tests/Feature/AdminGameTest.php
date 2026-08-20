<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Models\Axis;
use App\Models\Game;
use App\Models\Level;
use App\Models\Skill;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGameTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Subscriber $regularUser;
    protected Axis $axis;
    protected Skill $skill;
    protected Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->regularUser = Subscriber::create([
            'name' => 'Regular User',
            'phone' => '+9647702222222',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->axis = Axis::create([
            'name' => 'الانتباه والتركيز',
            'slug' => 'attention',
            'status' => 'ACTIVE',
        ]);

        $this->skill = Skill::create([
            'axis_id' => $this->axis->id,
            'name' => 'الانتباه الانتقائي',
            'slug' => 'selective-attention',
            'status' => 'ACTIVE',
        ]);

        $this->level = Level::create([
            'level_number' => 1,
            'name' => 'المستوى الأول',
            'min_age' => 3,
            'max_age' => 8,
        ]);
    }

    public function test_admin_can_create_and_publish_game(): void
    {
        $adminToken = $this->admin->createToken('admin_token')->plainTextToken;

        // 1. Create Game
        $createResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/admin/games', [
                'code' => 'TEST-001',
                'name' => 'لعبة النجوم',
                'type' => 'TAP',
                'axis_id' => $this->axis->id,
                'skill_id' => $this->skill->id,
                'level_id' => $this->level->id,
                'difficulty' => 'easy',
                'duration_seconds' => 60,
                'config' => [
                    'attempts' => 10,
                    'success_threshold' => 0.8,
                    'time_limit_seconds' => 60,
                ],
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.code', 'TEST-001')
            ->assertJsonPath('data.status', 'DRAFT');

        $gameId = $createResponse->json('data.id');

        // 2. Validate Game
        $validateResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/games/{$gameId}/validate");
        $validateResponse->assertStatus(200);

        // 3. Approve Game
        $approveResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/games/{$gameId}/approve");
        $approveResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'APPROVED');

        // 4. Publish Game
        $publishResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/games/{$gameId}/publish");
        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'PUBLISHED');

        $this->assertDatabaseHas('games', [
            'id' => $gameId,
            'status' => 'PUBLISHED',
        ]);
    }

    public function test_regular_user_is_forbidden_from_admin_endpoints(): void
    {
        $userToken = $this->regularUser->createToken('user_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
