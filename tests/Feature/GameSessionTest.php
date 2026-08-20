<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Models\Axis;
use App\Models\Curriculum;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Skill;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Subscriber $user;
    protected Subscriber $otherUser;
    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647704444444',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->otherUser = Subscriber::create([
            'name' => 'Other',
            'phone' => '+9647705555555',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $axis = Axis::create([
            'name' => 'الانتباه',
            'slug' => 'attention',
            'status' => 'ACTIVE',
        ]);

        $skill = Skill::create([
            'axis_id' => $axis->id,
            'name' => 'الانتباه المستمر',
            'slug' => 'sustained-attention',
            'status' => 'ACTIVE',
        ]);

        $this->game = Game::create([
            'code' => 'GAME-001',
            'name' => 'لعبة التركيز',
            'slug' => 'focus-game',
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

    public function test_user_can_start_and_complete_game_session(): void
    {
        $token = $this->user->createToken('user')->plainTextToken;

        // 1. Start Session
        $startResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/app/games/{$this->game->id}/sessions");

        $startResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'STARTED');

        $sessionId = $startResponse->json('data.id');

        // 2. Complete Session
        $completeResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/app/sessions/{$sessionId}/complete", [
                'attempts' => 10,
                'correct_attempts' => 8,
                'incorrect_attempts' => 2,
                'duration_seconds' => 35,
                'score' => 80,
            ]);

        $completeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'COMPLETED')
            ->assertJsonPath('data.accuracy', 80);

        $this->assertDatabaseHas('game_sessions', [
            'id' => $sessionId,
            'status' => 'COMPLETED',
            'score' => 80,
        ]);
    }

    public function test_user_cannot_complete_session_belonging_to_another_user(): void
    {
        $session = GameSession::create([
            'subscriber_id' => $this->user->id,
            'game_id' => $this->game->id,
            'started_at' => now(),
            'status' => 'STARTED',
        ]);

        $otherToken = $this->otherUser->createToken('other')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->postJson("/api/v1/app/sessions/{$session->id}/complete", [
                'attempts' => 5,
                'correct_attempts' => 5,
                'duration_seconds' => 20,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'UNAUTHORIZED_GAME_SESSION');
    }

    public function test_user_can_abandon_a_started_session(): void
    {
        $token = $this->user->createToken('user')->plainTextToken;

        $startResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/app/games/{$this->game->id}/sessions");
        $sessionId = $startResponse->json('data.id');

        $abandonResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/app/sessions/{$sessionId}/abandon");

        $abandonResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'ABANDONED');

        $this->assertDatabaseHas('game_sessions', [
            'id' => $sessionId,
            'status' => 'ABANDONED',
        ]);
    }

    public function test_user_cannot_abandon_a_session_belonging_to_another_user(): void
    {
        $session = GameSession::create([
            'subscriber_id' => $this->user->id,
            'game_id' => $this->game->id,
            'started_at' => now(),
            'status' => 'STARTED',
        ]);

        $otherToken = $this->otherUser->createToken('other')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->postJson("/api/v1/app/sessions/{$session->id}/abandon");

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'UNAUTHORIZED_GAME_SESSION');
    }
}
