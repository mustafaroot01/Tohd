<?php

namespace Tests\Feature;

use App\Actions\Curriculum\AttachGameToDayAction;
use App\Actions\Curriculum\CreateCurriculumAction;
use App\Actions\Curriculum\PublishCurriculumAction;
use App\Actions\Games\CreateGameAction;
use App\Actions\Games\PublishGameAction;
use App\Enums\GameType;
use App\Models\ActivationCode;
use App\Models\Axis;
use App\Models\Level;
use App\Models\Product;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTrainingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_end_to_end_user_training_flow(): void
    {
        // 1. Setup Admin
        $admin = User::create([
            'name' => 'Admin Faris',
            'email' => 'admin@rihla.com',
            'password' => bcrypt('password123'),
            'status' => 'ACTIVE',
        ]);

        // 2. Create Axis & Skill
        $axis = Axis::create([
            'name' => 'التركيز والانتباه',
            'slug' => 'attention',
            'status' => 'ACTIVE',
        ]);

        $skill = Skill::create([
            'axis_id' => $axis->id,
            'name' => 'الانتباه الانتقائي',
            'slug' => 'selective-attention',
            'status' => 'ACTIVE',
        ]);

        // 3. Admin creates and publishes Game
        $level = Level::create([
            'level_number' => 1,
            'name' => 'المستوى الأول',
            'min_age' => 3,
            'max_age' => 8,
        ]);

        $createGameAction = app(CreateGameAction::class);
        $publishGameAction = app(PublishGameAction::class);

        $game = $createGameAction->execute([
            'code' => 'ATT-001',
            'name' => 'صيد النجوم',
            'type' => GameType::TAP,
            'axis_id' => $axis->id,
            'skill_id' => $skill->id,
            'level_id' => $level->id,
            'config' => [
                'attempts' => 10,
                'success_threshold' => 0.8,
                'time_limit_seconds' => 60,
            ],
        ], $admin);

        $publishGameAction->execute($game, $admin);

        // 4. Admin creates Curriculum with Month, Week, Day and attaches Game
        $createCurrAction = app(CreateCurriculumAction::class);
        $curriculum = $createCurrAction->execute([
            'code' => 'CURR-001',
            'name' => 'منهج التأسيس الأول',
        ], $admin);

        $month = $curriculum->months()->create([
            'month_number' => 1,
            'name' => 'الشهر 1',
        ]);

        $week = $month->weeks()->create([
            'week_number' => 1,
            'name' => 'الأسبوع 1',
        ]);

        $day = $week->days()->create([
            'day_number' => 1,
            'name' => 'اليوم 1',
            'estimated_duration_seconds' => 600,
        ]);

        $attachAction = app(AttachGameToDayAction::class);
        $attachAction->execute($day, $game, ['is_required' => true]);

        $publishCurrAction = app(PublishCurriculumAction::class);
        $publishCurrAction->execute($curriculum, $admin);

        // 5. Admin creates Product and generates Activation Code
        $product = Product::create([
            'code' => 'PROD-30D',
            'name' => 'باقة 30 يوماً',
            'slug' => 'prod-30d',
            'curriculum_id' => $curriculum->id,
            'duration_days' => 30,
            'status' => 'ACTIVE',
            'price' => 150,
        ]);

        $activationCode = ActivationCode::create([
            'code' => 'RIHLA-2026-HERO-0001',
            'product_id' => $product->id,
            'status' => 'AVAILABLE',
        ]);

        // 6. User registers, verifies phone via OTP, and receives a token
        $registerRes = $this->postJson('/api/v1/app/auth/register', [
            'name' => 'فارس الصغير',
            'phone' => '07701239999',
            'password' => 'secret123456',
        ]);
        $registerRes->assertStatus(201);

        $otpCode = $this->fakeSmsGateway()->lastCodeFor('+9647701239999');

        $verifyRes = $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => '07701239999',
            'code' => $otpCode,
        ]);
        $verifyRes->assertStatus(200);
        $userToken = $verifyRes->json('data.token');

        // 7. User redeems Activation Code
        $redeemRes = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->postJson('/api/v1/app/activations/redeem', [
                'code' => 'RIHLA-2026-HERO-0001',
            ]);
        $redeemRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 8. User fetches today's training plan
        $todayRes = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->getJson('/api/v1/app/curriculum/today');
        $todayRes->assertStatus(200)
            ->assertJsonPath('data.curriculum.code', 'CURR-001')
            ->assertJsonCount(1, 'data.games');

        // 9. User starts a Game Session
        $startSessionRes = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->postJson("/api/v1/app/games/{$game->id}/sessions", [
                'curriculum_day_id' => $day->id,
            ]);
        $startSessionRes->assertStatus(201)
            ->assertJsonPath('data.status', 'STARTED');

        $sessionId = $startSessionRes->json('data.id');

        // 10. User completes the Game Session
        $completeSessionRes = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->postJson("/api/v1/app/sessions/{$sessionId}/complete", [
                'attempts' => 10,
                'correct_attempts' => 9,
                'incorrect_attempts' => 1,
                'duration_seconds' => 45,
                'score' => 95,
            ]);
        $completeSessionRes->assertStatus(200)
            ->assertJsonPath('data.status', 'COMPLETED')
            ->assertJsonPath('data.accuracy', 90);

        // 11. User checks overall Progress
        $progressRes = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->getJson('/api/v1/app/progress');
        $progressRes->assertStatus(200)
            ->assertJsonPath('data.total_sessions', 1)
            ->assertJsonPath('data.total_games_completed', 1)
            ->assertJsonPath('data.average_accuracy', 90)
            ->assertJsonPath('data.total_score', 95);

        // 12. User checks Home API
        $homeRes = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->getJson('/api/v1/app/home');
        $homeRes->assertStatus(200)
            ->assertJsonPath('data.user.phone', '+9647701239999')
            ->assertJsonPath('data.today.day.is_completed', true);
    }
}
