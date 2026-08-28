<?php

namespace Tests\Feature;

use App\Enums\GameProgressStatus;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Models\SubscriberGameProgress;
use App\Services\ProgressBoardService;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * One attempt lands in exactly two places: the child's row for the game, and
 * the day's row. These pin down what each row means and that the board can
 * always be rebuilt from the days.
 */
class ProgressBoardTest extends TestCase
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

    public function test_starting_a_game_writes_nothing(): void
    {
        $this->issueToken($this->child, $this->game);

        $this->assertSame(0, SubscriberGameProgress::count());
        $this->assertSame(0, SubscriberGameDaily::count());
        $this->assertNull($this->child->fresh()->last_attempt_started_us);
    }

    public function test_one_row_per_child_and_game_is_created_on_the_first_result(): void
    {
        $this->play($this->child, $this->game, 30); // 5/10 failed
        $this->play($this->child, $this->game, 54); // 9/10 passed

        $this->assertSame(1, SubscriberGameProgress::count());

        $r = $this->row();
        $this->assertSame(2, $r->total_attempts);
        $this->assertSame(1, $r->total_passed);
        $this->assertSame(0, $r->total_short);
        $this->assertSame(14, $r->total_score_sum);
        $this->assertSame(84, $r->total_attention_seconds);
        $this->assertSame(9, $r->best_score);
        $this->assertSame(54, $r->best_seconds);
        $this->assertNotNull($r->passed_at);
        $this->assertSame(9, $r->last_score);
        $this->assertTrue($r->last_passed);
    }

    public function test_one_row_per_day_holds_the_same_attempts(): void
    {
        $this->play($this->child, $this->game, 30);
        $this->play($this->child, $this->game, 54);

        $this->assertSame(1, SubscriberGameDaily::count());

        $d = $this->day();
        $this->assertSame(now()->toDateString(), $d->date->toDateString());
        $this->assertSame(2, $d->attempts);
        $this->assertSame(1, $d->failed);
        $this->assertSame(0, $d->short);
        $this->assertSame(9, $d->best_score);
        $this->assertSame(14, $d->score_sum);
        $this->assertSame(84, $d->attention_seconds);
        $this->assertNotNull($d->passed_at);
        $this->assertNull($d->skipped_at);
        // the bar is frozen on the day it was played against
        $this->assertSame(8, $d->required_score);
        $this->assertSame(60, $d->required_seconds);
    }

    public function test_best_score_never_goes_down(): void
    {
        $this->play($this->child, $this->game, 54);
        $this->play($this->child, $this->game, 18);

        $r = $this->row();
        $this->assertSame(9, $r->best_score);
        $this->assertSame(3, $r->last_score);
        $this->assertFalse($r->last_passed);
        $this->assertNotNull($r->passed_at);
    }

    public function test_a_short_attempt_is_recorded_but_is_not_a_failure(): void
    {
        $this->play($this->child, $this->game, 3);

        $r = $this->row();
        $this->assertSame(1, $r->total_attempts);
        $this->assertSame(1, $r->today_short);
        $this->assertSame(0, $r->today_failed);
        $this->assertSame(1, $this->day()->short);
        $this->assertSame(0, $this->day()->failed);
    }

    public function test_an_attempt_too_short_to_count_cannot_pass_however_low_the_bar(): void
    {
        // bar 2/10 = 12 s of a 60 s game, but 15 s is the shortest real try
        $easy = $this->makeGame(['config' => ['success_threshold' => 0.2, 'attempts' => 10]]);
        $this->play($this->child, $easy, 12);

        $row = fn () => SubscriberGameProgress::where('game_id', $easy->id)->firstOrFail();
        $this->assertSame(1, $row()->total_attempts);
        $this->assertSame(1, $row()->total_short);
        $this->assertSame(0, $row()->total_passed);
        $this->assertNull($row()->passed_at);
        $this->assertSame(GameProgressStatus::IN_PROGRESS, app(ScoringService::class)->progressFor($easy, $row())['status']);

        // and the rebuild from the days says the same
        app(ProgressBoardService::class)->rebuildFor($this->child);
        $this->assertSame(0, $row()->total_passed);
        $this->assertSame(1, $row()->total_short);
    }

    public function test_today_counters_reset_on_the_first_write_of_a_new_day_and_the_day_keeps_its_row(): void
    {
        $this->travelTo(now()->subDay()->setTime(10, 0));
        $this->play($this->child, $this->game, 18);
        $this->play($this->child, $this->game, 18);

        $r = $this->row();
        $this->assertSame(2, $r->today_attempts);
        $this->assertTrue($r->isForToday());

        $this->travelTo(now()->addDay()->setTime(10, 0));
        // the clock moved on: yesterday's counters no longer describe today
        $this->assertFalse($this->row()->isForToday());
        $this->play($this->child, $this->game, 24);

        $r = $this->row();
        $this->assertTrue($r->isForToday());
        $this->assertSame(1, $r->today_attempts);
        $this->assertSame(1, $r->today_failed);
        $this->assertSame(4, $r->today_best_score);
        $this->assertSame(3, $r->total_attempts);

        // yesterday is untouched, today is its own row
        $this->assertSame(2, SubscriberGameDaily::count());
        $this->assertSame(2, SubscriberGameDaily::where('date', now()->subDay()->toDateString())->first()->attempts);
    }

    public function test_a_skip_is_written_to_both_rows_and_cleared_from_the_board_next_day(): void
    {
        $board = app(ProgressBoardService::class);
        $now = now();

        $row = SubscriberGameProgress::create(['subscriber_id' => $this->child->id, 'game_id' => $this->game->id]);
        $board->recordSkip($row, $this->game, $now, null, 8, 60);

        $this->assertNotNull($this->row()->skipped_at);
        $this->assertNotNull($this->day()->skipped_at);
        $this->assertSame(0, $this->day()->attempts);

        $this->travelTo($now->copy()->addDay());
        $this->play($this->child, $this->game, 18);

        $this->assertNull($this->row()->skipped_at);
        // …but the day it happened on still says so
        $this->assertNotNull(SubscriberGameDaily::where('date', $now->toDateString())->first()->skipped_at);
    }

    public function test_rebuilding_from_the_days_reproduces_the_row(): void
    {
        $this->travelTo(now()->subDays(2)->setTime(9, 0));
        $this->play($this->child, $this->game, 30);
        $this->travelTo(now()->addDays(2)->setTime(9, 0));
        $this->play($this->child, $this->game, 54);
        $this->play($this->child, $this->game, 3);
        $this->play($this->child, $this->game, 18);

        $keys = ['best_score', 'best_seconds', 'total_attempts', 'total_passed', 'total_short', 'total_score_sum',
            'total_attention_seconds', 'today_attempts', 'today_failed', 'today_short', 'today_best_score', 'last_score', 'last_seconds', 'last_passed'];
        $live = $this->row()->only($keys);
        $livePassedAt = $this->row()->passed_at->toDateTimeString();

        $rebuilt = app(ProgressBoardService::class)->rebuildFor($this->child);

        $this->assertSame(1, $rebuilt);
        $this->assertSame($live, $this->row()->only($keys));
        $this->assertSame($livePassedAt, $this->row()->passed_at->toDateTimeString());
        // the replay watermark is not part of the board and survives untouched
        $this->assertNotNull($this->child->fresh()->last_attempt_started_us);
    }

    public function test_the_artisan_rebuild_command_runs(): void
    {
        $this->play($this->child, $this->game, 54);
        SubscriberGameProgress::query()->delete();

        $this->artisan('app:rebuild-progress-board')->assertSuccessful();

        $this->assertSame(9, $this->row()->best_score);
    }

    private function row(): SubscriberGameProgress
    {
        return SubscriberGameProgress::where('subscriber_id', $this->child->id)->where('game_id', $this->game->id)->firstOrFail();
    }

    private function day(): SubscriberGameDaily
    {
        return SubscriberGameDaily::where('subscriber_id', $this->child->id)
            ->where('game_id', $this->game->id)
            ->where('date', now()->toDateString())
            ->firstOrFail();
    }
}
