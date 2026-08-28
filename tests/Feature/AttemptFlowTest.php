<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Models\SubscriberGameProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * One attempt, start to finish, over HTTP — and every way a modified app
 * could try to be credited for attention it did not spend.
 */
class AttemptFlowTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Game $game;

    protected Subscriber $child;

    protected array $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = $this->makeGame();
        $this->child = $this->makeChild();
        $this->auth = $this->bearer($this->child);
    }

    private function start(?Game $game = null)
    {
        $game ??= $this->game;
        $this->app['auth']->forgetGuards();

        return $this->withHeaders($this->auth)->postJson("/api/v1/app/games/{$game->id}/attempts");
    }

    private function complete(string $token, ?int $claimed = null, ?Game $game = null)
    {
        $game ??= $this->game;
        $this->app['auth']->forgetGuards();
        $body = ['attempt_token' => $token];
        if ($claimed !== null) {
            $body['duration_seconds'] = $claimed;
        }

        return $this->withHeaders($this->auth)->postJson("/api/v1/app/games/{$game->id}/attempts/complete", $body);
    }

    public function test_there_is_no_sessions_table_any_more(): void
    {
        $this->assertFalse(Schema::hasTable('game_sessions'));
        $this->assertFalse(Schema::hasTable('user_skill_progress'));
        $this->assertTrue(Schema::hasTable('subscriber_game_daily'));
    }

    public function test_starting_hands_back_a_token_and_writes_nothing(): void
    {
        $res = $this->start()
            ->assertStatus(201)
            ->assertJsonPath('data.expires_in_seconds', 660)
            ->assertJsonPath('data.game.required_seconds', 60)
            ->assertJsonPath('data.game.required_score', 8)
            ->assertJsonPath('data.game.min_counted_seconds', 15)
            ->assertJsonPath('data.progress.status', 'NOT_STARTED');

        $this->assertNotEmpty($res->json('data.attempt_token'));
        $this->assertSame(0, SubscriberGameProgress::count());
        $this->assertSame(0, SubscriberGameDaily::count());
    }

    public function test_the_grade_is_measured_on_the_server_not_claimed_by_the_app(): void
    {
        $token = $this->start()->json('data.attempt_token');

        $this->travelTo(now()->addSeconds(49));

        // the app claims a full minute after 49 seconds of wall clock
        $this->complete($token, 60)
            ->assertStatus(200)
            ->assertJsonPath('data.attempt.claimed_seconds', 60)
            ->assertJsonPath('data.attempt.elapsed_seconds', 49)
            ->assertJsonPath('data.attempt.effective_seconds', 49)
            ->assertJsonPath('data.attempt.score', 8)
            ->assertJsonPath('data.attempt.is_passed', true)
            ->assertJsonPath('data.attempt.is_counted', true)
            ->assertJsonPath('data.attempt.is_replayed', false)
            ->assertJsonPath('data.game.status', 'PASSED')
            ->assertJsonPath('data.game.score', 8);

        $this->assertSame(1, SubscriberGameProgress::count());
        $this->assertSame(1, SubscriberGameDaily::count());
        $this->assertNotNull($this->child->fresh()->last_attempt_started_us);
    }

    public function test_the_apps_own_claim_can_only_lower_the_grade(): void
    {
        $token = $this->start()->json('data.attempt_token');
        $this->travelTo(now()->addSeconds(60));

        $this->complete($token, 20)
            ->assertJsonPath('data.attempt.effective_seconds', 20)
            ->assertJsonPath('data.attempt.score', 3)
            ->assertJsonPath('data.attempt.is_passed', false);
    }

    public function test_resending_the_same_result_is_answered_again_not_counted_again(): void
    {
        $token = $this->start()->json('data.attempt_token');
        $this->travelTo(now()->addSeconds(54));
        $this->complete($token)->assertStatus(200)->assertJsonPath('data.attempt.score', 9);

        $this->travelTo(now()->addSeconds(3));
        $this->complete($token)
            ->assertStatus(200)
            ->assertJsonPath('data.attempt.is_replayed', true)
            ->assertJsonPath('data.attempt.score', 9)
            ->assertJsonPath('data.attempt.is_passed', true);

        $this->assertSame(1, SubscriberGameProgress::first()->total_attempts);
    }

    public function test_an_older_token_than_the_last_graded_one_is_refused(): void
    {
        $older = $this->start()->json('data.attempt_token');
        $this->travelTo(now()->addSecond());
        $newer = $this->start()->json('data.attempt_token');
        $this->travelTo(now()->addSeconds(54));

        $this->complete($newer)->assertStatus(200);
        $this->complete($older)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ATTEMPT_ALREADY_USED');
    }

    public function test_two_tokens_cannot_both_be_credited_the_same_minute(): void
    {
        $other = $this->makeGame();
        $a = $this->start()->json('data.attempt_token');
        $b = $this->start($other)->json('data.attempt_token');

        $this->travelTo(now()->addSeconds(60));

        $this->complete($a)->assertJsonPath('data.attempt.effective_seconds', 60);
        // the same sixty seconds were already credited to the first game
        $this->complete($b, null, $other)
            ->assertStatus(200)
            ->assertJsonPath('data.attempt.effective_seconds', 0)
            ->assertJsonPath('data.attempt.score', 0)
            ->assertJsonPath('data.attempt.is_counted', false);
    }

    public function test_a_tampered_token_is_refused(): void
    {
        $token = $this->start()->json('data.attempt_token');

        $this->complete(substr($token, 0, -4).'AAAA')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_ATTEMPT_TOKEN');

        $this->complete(str_repeat('A', 5000))->assertStatus(422);
    }

    public function test_a_token_for_another_child_or_another_game_is_refused_with_the_same_answer(): void
    {
        $other = $this->makeChild();
        $this->app['auth']->forgetGuards();
        $theirs = $this->withHeaders($this->bearer($other))
            ->postJson("/api/v1/app/games/{$this->game->id}/attempts")
            ->json('data.attempt_token');

        $this->complete($theirs)->assertStatus(422)->assertJsonPath('error_code', 'INVALID_ATTEMPT_TOKEN');

        $otherGame = $this->makeGame();
        $mine = $this->start()->json('data.attempt_token');
        $this->complete($mine, null, $otherGame)->assertStatus(422)->assertJsonPath('error_code', 'INVALID_ATTEMPT_TOKEN');
    }

    public function test_a_token_expires_after_the_game_plus_the_grace_period(): void
    {
        $token = $this->start()->json('data.attempt_token');
        $this->travelTo(now()->addSeconds(60 + 600 + 1));

        $this->complete($token)
            ->assertStatus(410)
            ->assertJsonPath('error_code', 'ATTEMPT_EXPIRED');
    }

    public function test_a_days_attempts_are_capped(): void
    {
        config(['scoring.max_attempts_per_day' => 2]);

        $this->play($this->child, $this->game, 5);
        $this->play($this->child, $this->game, 5);

        $token = $this->start()->json('data.attempt_token');
        $this->travelTo(now()->addSeconds(5));
        $this->complete($token)
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'ATTEMPT_LIMIT_REACHED');
    }

    public function test_an_unpublished_game_cannot_be_started(): void
    {
        $draft = $this->makeGame(['status' => 'DRAFT']);

        $this->start($draft)->assertStatus(403);
    }
}
