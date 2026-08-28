<?php

namespace Tests\Feature;

use App\Enums\SubscriberActivityType;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * Skipping is a recorded decision, gated by counted failures.
 */
class GameSkipTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Game $game;

    protected Subscriber $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = $this->makeGame();
        $this->child = $this->makeChild();
    }

    private function failedAttempt(): void
    {
        $this->play($this->child, $this->game, 18); // 3/10, long enough to count
    }

    private function skip()
    {
        return $this->withHeaders($this->bearer($this->child))
            ->postJson("/api/v1/app/games/{$this->game->id}/skip");
    }

    public function test_skipping_is_refused_before_three_counted_failures(): void
    {
        $this->failedAttempt();
        $this->failedAttempt();

        $this->skip()
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'GAME_SKIP_NOT_ALLOWED');

        $this->assertDatabaseMissing('subscriber_activities', [
            'subscriber_id' => $this->child->id,
            'type' => SubscriberActivityType::GAME_SKIPPED->value,
        ]);
    }

    public function test_three_short_attempts_do_not_earn_a_skip(): void
    {
        foreach ([1, 2, 3] as $_) {
            $this->play($this->child, $this->game, 2);
        }

        $this->skip()->assertStatus(422);
    }

    public function test_skipping_is_recorded_after_three_failed_attempts(): void
    {
        $this->failedAttempt();
        $this->failedAttempt();
        $this->failedAttempt();

        $this->skip()
            ->assertStatus(200)
            ->assertJsonPath('data.failed_attempts', 3)
            ->assertJsonPath('data.best_score', 3)
            ->assertJsonPath('data.game.status', 'SKIPPED');

        $this->assertDatabaseHas('subscriber_activities', [
            'subscriber_id' => $this->child->id,
            'type' => SubscriberActivityType::GAME_SKIPPED->value,
        ]);
        $this->assertNotNull(SubscriberGameDaily::where('subscriber_id', $this->child->id)->first()->skipped_at);
    }

    public function test_a_second_skip_the_same_day_does_not_duplicate_the_record(): void
    {
        $this->failedAttempt();
        $this->failedAttempt();
        $this->failedAttempt();

        $this->skip()->assertStatus(200);
        $this->skip()->assertStatus(200)->assertJsonPath('data.game.status', 'SKIPPED');

        $this->assertSame(1, $this->child->activities()
            ->where('type', SubscriberActivityType::GAME_SKIPPED)
            ->count());
    }

    public function test_a_passed_game_cannot_be_skipped(): void
    {
        $this->play($this->child, $this->game, 55);

        $this->skip()->assertStatus(422);
    }

    public function test_the_skip_gate_is_configurable(): void
    {
        config(['scoring.attempts_before_unlock' => 1]);
        $this->failedAttempt();

        $this->skip()->assertStatus(200);
    }

    public function test_skipping_can_be_disabled_entirely(): void
    {
        config(['scoring.attempts_before_unlock' => 0]);
        foreach ([1, 2, 3, 4] as $_) {
            $this->failedAttempt();
        }

        $this->skip()->assertStatus(422);
    }

    public function test_another_subscriber_cannot_skip_on_this_childs_behalf(): void
    {
        $other = $this->makeChild();

        $this->failedAttempt();
        $this->failedAttempt();
        $this->failedAttempt();

        // the other child has no failed attempts on this game, so the gate refuses them
        $this->withHeaders($this->bearer($other))
            ->postJson("/api/v1/app/games/{$this->game->id}/skip")
            ->assertStatus(422);
    }
}
