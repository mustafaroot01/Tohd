<?php

namespace Tests\Feature;

use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\CurriculumDay;
use App\Models\CurriculumDayGame;
use App\Models\CurriculumMonth;
use App\Models\CurriculumWeek;
use App\Models\Game;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Models\UserCurriculumAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * Today's plan walks the day in order: each game is locked until the ones before
 * it were passed or skipped, and only a pass counts toward finishing the day.
 */
class DayProgressGateTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Subscriber $child;

    /** @var array<int, Game> */
    protected array $games = [];

    protected CurriculumDay $day;

    /** a later day of the same plan that reuses game 1 */
    protected CurriculumDay $laterDay;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1, 2, 3] as $i) {
            $this->games[$i] = $this->makeGame(['code' => "G-$i", 'name' => "لعبة $i", 'slug' => "g-$i"]);
        }

        $curriculum = Curriculum::create(['code' => 'C-1', 'name' => 'منهج', 'slug' => 'c-1', 'status' => 'PUBLISHED', 'version' => 1]);
        $month = CurriculumMonth::create(['curriculum_id' => $curriculum->id, 'month_number' => 1, 'name' => 'شهر', 'sort_order' => 1]);
        $week = CurriculumWeek::create(['curriculum_month_id' => $month->id, 'week_number' => 1, 'name' => 'أسبوع', 'sort_order' => 1]);
        $this->day = CurriculumDay::create(['curriculum_week_id' => $week->id, 'day_number' => 1, 'name' => 'يوم', 'sort_order' => 1, 'estimated_duration_seconds' => 180]);

        foreach ($this->games as $i => $game) {
            CurriculumDayGame::create(['curriculum_day_id' => $this->day->id, 'game_id' => $game->id, 'sort_order' => $i, 'is_required' => true]);
        }

        $this->laterDay = CurriculumDay::create(['curriculum_week_id' => $week->id, 'day_number' => 2, 'name' => 'يوم 2', 'sort_order' => 2, 'estimated_duration_seconds' => 180]);
        CurriculumDayGame::create(['curriculum_day_id' => $this->laterDay->id, 'game_id' => $this->games[1]->id, 'sort_order' => 1, 'is_required' => true]);

        $this->child = $this->makeChild();

        $product = Product::create(['code' => 'P-1', 'name' => 'باقة', 'slug' => 'p-1', 'curriculum_id' => $curriculum->id, 'duration_days' => 30, 'price' => 1, 'status' => 'ACTIVE']);
        $activation = ActivationCode::create(['code' => 'CODE-1', 'product_id' => $product->id, 'status' => 'ACTIVATED', 'activated_by' => $this->child->id, 'activated_at' => now()]);

        UserCurriculumAssignment::create([
            'subscriber_id' => $this->child->id, 'curriculum_id' => $curriculum->id, 'activation_id' => $activation->id,
            'starts_at' => now()->startOfDay(), 'ends_at' => now()->addDays(30), 'status' => 'ACTIVE',
        ]);
    }

    /** Six seconds of attention per tenth on a 60-second game. */
    private function score(Game $game, int $tenths): void
    {
        $this->play($this->child, $game, $tenths * 6, dayId: $this->day->id);
    }

    private function today(): array
    {
        return $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/curriculum/today')
            ->assertStatus(200)
            ->json('data.games');
    }

    private function byCode(array $games, string $code): array
    {
        foreach ($games as $g) {
            if ($g['code'] === $code) {
                return $g;
            }
        }
        $this->fail("game $code missing from today's plan");
    }

    public function test_only_the_first_game_is_open_on_a_fresh_day(): void
    {
        $games = $this->today();

        $this->assertFalse($this->byCode($games, 'G-1')['is_locked']);
        $this->assertTrue($this->byCode($games, 'G-2')['is_locked']);
        $this->assertTrue($this->byCode($games, 'G-3')['is_locked']);
        $this->assertSame('NOT_STARTED', $this->byCode($games, 'G-1')['status']);
    }

    public function test_passing_a_game_unlocks_the_next_one(): void
    {
        $this->score($this->games[1], 9);

        $games = $this->today();
        $g1 = $this->byCode($games, 'G-1');

        $this->assertSame('PASSED', $g1['status']);
        $this->assertTrue($g1['is_completed']);
        $this->assertSame(9, $g1['score']);
        $this->assertSame(8, $g1['required_score']);
        $this->assertFalse($this->byCode($games, 'G-2')['is_locked']);
        $this->assertTrue($this->byCode($games, 'G-3')['is_locked']);
    }

    public function test_an_attempt_on_a_day_of_the_plan_is_attributed_to_that_day(): void
    {
        $this->score($this->games[1], 9);

        $this->assertSame($this->day->id, SubscriberGameDaily::first()->curriculum_day_id);
    }

    public function test_a_pass_on_an_earlier_day_does_not_finish_the_game_today(): void
    {
        $this->travelTo(now()->subDay()->setTime(10, 0));
        $this->score($this->games[1], 9);

        $this->travelTo(now()->addDay()->setTime(10, 0));
        $games = $this->today();
        $g1 = $this->byCode($games, 'G-1');

        // the plan is daily practice: yesterday's pass is history, not today's work
        $this->assertSame('IN_PROGRESS', $g1['status']);
        $this->assertFalse($g1['is_completed']);
        $this->assertSame(9, $g1['score']);
        $this->assertTrue($this->byCode($games, 'G-2')['is_locked']);
    }

    public function test_an_attempt_claimed_for_a_day_the_child_is_not_on_is_free_play(): void
    {
        $this->play($this->child, $this->games[1], 54, dayId: $this->laterDay->id);

        $this->assertNull(SubscriberGameDaily::first()->curriculum_day_id);
    }

    public function test_a_completed_but_failed_game_keeps_the_next_one_locked(): void
    {
        $this->score($this->games[1], 4);

        $games = $this->today();
        $g1 = $this->byCode($games, 'G-1');

        $this->assertSame('IN_PROGRESS', $g1['status']);
        $this->assertFalse($g1['is_completed']);
        $this->assertFalse($g1['can_skip']);
        $this->assertTrue($this->byCode($games, 'G-2')['is_locked']);
    }

    public function test_the_skip_offer_appears_after_three_failures_but_the_status_stays_in_progress(): void
    {
        foreach ([1, 2, 3] as $_) {
            $this->score($this->games[1], 3);
        }

        $g1 = $this->byCode($this->today(), 'G-1');

        $this->assertSame('IN_PROGRESS', $g1['status']);
        $this->assertTrue($g1['can_skip']);
        $this->assertSame(3, $g1['failed_attempts']);
    }

    public function test_a_recorded_skip_unlocks_the_next_game_without_counting_as_done(): void
    {
        foreach ([1, 2, 3] as $_) {
            $this->score($this->games[1], 3);
        }

        $this->withHeaders($this->bearer($this->child))
            ->postJson("/api/v1/app/games/{$this->games[1]->id}/skip", ['curriculum_day_id' => $this->day->id])
            ->assertStatus(200);

        $games = $this->today();
        $g1 = $this->byCode($games, 'G-1');

        $this->assertSame('SKIPPED', $g1['status']);
        $this->assertFalse($g1['is_completed']);
        $this->assertFalse($g1['can_skip']);
        $this->assertFalse($this->byCode($games, 'G-2')['is_locked']);
    }

    public function test_day_completion_counts_passes_only(): void
    {
        foreach ([1, 2, 3] as $_) {
            $this->score($this->games[1], 3);
        }
        $this->withHeaders($this->bearer($this->child))
            ->postJson("/api/v1/app/games/{$this->games[1]->id}/skip")
            ->assertStatus(200);
        $this->score($this->games[2], 10);
        $this->score($this->games[3], 8);

        $res = $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/curriculum/today')
            ->assertStatus(200);

        // three games, one skipped, two passed → 2 of 3 done, and the day is not finished
        $this->assertEquals(66.7, $res->json('data.day.completion_rate'));
        $this->assertFalse($res->json('data.day.is_completed'));
        $this->assertCount(2, array_filter($res->json('data.games'), fn ($g) => $g['is_completed']));
    }

    public function test_the_gate_can_be_switched_off(): void
    {
        config(['scoring.require_pass_to_advance' => false]);

        $games = $this->today();

        $this->assertFalse($this->byCode($games, 'G-2')['is_locked']);
        $this->assertFalse($this->byCode($games, 'G-3')['is_locked']);
    }
}
