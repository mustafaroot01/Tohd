<?php

namespace Tests\Feature;

use App\Enums\GameProgressStatus;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameProgress;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * The grade is attention held against attention asked for, in whole tenths,
 * decided on the server.
 */
class ScoringTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Game $game;

    protected Subscriber $child;

    protected ScoringService $scoring;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = $this->makeGame();   // 60 s, bar 8/10 → 48 s
        $this->child = $this->makeChild();
        $this->scoring = app(ScoringService::class);
    }

    public function test_the_grade_is_attention_held_over_attention_asked_for_in_whole_tenths(): void
    {
        $this->assertSame(10, $this->scoring->score(60, 60));
        $this->assertSame(8, $this->scoring->score(48, 60));
        $this->assertSame(5, $this->scoring->score(30, 60));
        $this->assertSame(0, $this->scoring->score(0, 60));
    }

    public function test_seven_and_a_half_tenths_reads_seven_not_eight(): void
    {
        // 75 % attention must not be shown as "8/10 — passed" against an 80 % bar
        $this->assertSame(7, $this->scoring->score(45, 60));
        $this->assertFalse($this->scoring->passes(7, $this->game));
        $this->assertSame(8, $this->scoring->score(48, 60));
        $this->assertTrue($this->scoring->passes(8, $this->game));
    }

    public function test_watching_longer_than_the_game_does_not_beat_full_marks(): void
    {
        $this->assertSame(10, $this->scoring->score(600, 60));
    }

    public function test_the_pass_mark_comes_from_the_games_own_threshold(): void
    {
        $this->assertSame(8, $this->scoring->requiredScore($this->game));

        $stricter = $this->makeGame(['config' => ['success_threshold' => 0.9, 'attempts' => 10]]);
        $this->assertSame(9, $this->scoring->requiredScore($stricter));
    }

    public function test_effective_seconds_is_the_smallest_of_every_bound(): void
    {
        // the app's own claim only ever lowers the grade
        $this->assertSame(12, $this->scoring->effectiveSeconds(60, 12, $this->game));
        // the server's clock is the ceiling on the claim
        $this->assertSame(12, $this->scoring->effectiveSeconds(12, 60, $this->game));
        // time already credited to a previous attempt is not credited again
        $this->assertSame(10, $this->scoring->effectiveSeconds(60, null, $this->game, floor: 10));
        // and the game's own length caps everything
        $this->assertSame(60, $this->scoring->effectiveSeconds(600, null, $this->game));
    }

    public function test_an_attempt_shorter_than_a_quarter_of_the_game_is_not_a_real_try(): void
    {
        $this->assertSame(15, $this->scoring->minCountedSeconds($this->game));
        $this->assertFalse($this->scoring->counts(14, $this->game));
        $this->assertTrue($this->scoring->counts(15, $this->game));

        config(['scoring.min_counted_seconds' => 30, 'scoring.min_counted_ratio' => 0.1]);
        $this->assertSame(30, $this->scoring->minCountedSeconds($this->game));
    }

    public function test_a_game_never_played_reads_not_started_with_nothing_to_show(): void
    {
        $progress = $this->scoring->progressFor($this->game, null);

        $this->assertSame(GameProgressStatus::NOT_STARTED, $progress['status']);
        $this->assertNull($progress['score']);
        $this->assertSame(0, $progress['attempts']);
        $this->assertFalse($progress['can_skip']);
        $this->assertSame(8, $progress['required_score']);
        $this->assertSame(60, $progress['required_seconds']);
    }

    public function test_the_grade_is_the_best_attempt_of_the_day(): void
    {
        $this->play($this->child, $this->game, 30); // 5/10
        $this->play($this->child, $this->game, 54); // 9/10
        $this->play($this->child, $this->game, 18); // 3/10 — a distracted retry must not lower the reading

        $progress = $this->scoring->progressFor($this->game, $this->row());

        $this->assertSame(9, $progress['score']);
        $this->assertSame(GameProgressStatus::PASSED, $progress['status']);
        $this->assertSame(3, $progress['attempts']);
    }

    public function test_the_strategy_is_configurable(): void
    {
        $this->play($this->child, $this->game, 54); // 9/10, earlier
        $this->play($this->child, $this->game, 30); // 5/10, latest

        config(['scoring.strategy' => 'latest']);
        $this->assertSame(5, $this->scoring->progressFor($this->game, $this->row())['score']);

        config(['scoring.strategy' => 'best_of_day']);
        $this->assertSame(9, $this->scoring->progressFor($this->game, $this->row())['score']);

        config(['scoring.strategy' => 'best_all_time']);
        $this->assertSame(9, $this->scoring->progressFor($this->game, $this->row())['score']);
    }

    public function test_skipping_unlocks_only_after_three_counted_failures(): void
    {
        $this->play($this->child, $this->game, 18);
        $this->assertFalse($this->scoring->progressFor($this->game, $this->row())['can_skip']);

        $this->play($this->child, $this->game, 18);
        $this->assertFalse($this->scoring->progressFor($this->game, $this->row())['can_skip']);

        $this->play($this->child, $this->game, 18);
        $progress = $this->scoring->progressFor($this->game, $this->row());

        // the offer opens, but SKIPPED is only ever a recorded choice
        $this->assertTrue($progress['can_skip']);
        $this->assertSame(GameProgressStatus::IN_PROGRESS, $progress['status']);
        $this->assertSame(3, $progress['failed_attempts']);
    }

    public function test_short_attempts_do_not_count_toward_the_skip_offer(): void
    {
        foreach ([1, 2, 3, 4] as $_) {
            $this->play($this->child, $this->game, 3);
        }

        $progress = $this->scoring->progressFor($this->game, $this->row());

        $this->assertSame(4, $progress['attempts']);
        $this->assertSame(4, $progress['short_attempts']);
        $this->assertSame(0, $progress['failed_attempts']);
        $this->assertFalse($progress['can_skip']);
    }

    public function test_a_pass_ends_the_skip_offer(): void
    {
        foreach ([1, 2, 3] as $_) {
            $this->play($this->child, $this->game, 18);
        }
        $this->play($this->child, $this->game, 54); // finally passed

        $progress = $this->scoring->progressFor($this->game, $this->row());

        $this->assertSame(GameProgressStatus::PASSED, $progress['status']);
        $this->assertFalse($progress['can_skip']);
    }

    public function test_only_a_pass_counts_toward_the_day_when_the_gate_is_on(): void
    {
        config(['scoring.require_pass_to_advance' => true]);

        $this->play($this->child, $this->game, 30); // 5/10 — completed but not passed

        $this->assertFalse($this->scoring->countsAsDone($this->scoring->progressFor($this->game, $this->row())));
    }

    public function test_turning_the_gate_off_accepts_any_real_attempt(): void
    {
        config(['scoring.require_pass_to_advance' => false]);

        $this->play($this->child, $this->game, 30);

        $this->assertTrue($this->scoring->countsAsDone($this->scoring->progressFor($this->game, $this->row())));
    }

    private function row(): SubscriberGameProgress
    {
        return SubscriberGameProgress::where('subscriber_id', $this->child->id)->where('game_id', $this->game->id)->firstOrFail();
    }
}
