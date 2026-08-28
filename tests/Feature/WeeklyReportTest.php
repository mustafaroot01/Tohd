<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\WeekWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * Recorded daily, summed weekly: Saturday to Friday, Baghdad.
 */
class WeeklyReportTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Subscriber $child;

    protected Game $stars;

    protected Game $friend;

    public function test_a_friday_belongs_to_the_week_that_started_the_saturday_before(): void
    {
        $week = WeekWindow::containing('2026-08-28'); // a Friday

        $this->assertSame('2026-08-22', $week->start->toDateString());
        $this->assertSame('2026-08-28', $week->end->toDateString());
        $this->assertSame('2026-08-29', WeekWindow::containing('2026-08-29')->start->toDateString());
        $this->assertSame('2026-08-15', $week->previous()->start->toDateString());
        $this->assertSame(['2026-08-22', '2026-08-23', '2026-08-24', '2026-08-25', '2026-08-26', '2026-08-27', '2026-08-28'], $week->dates());
    }

    public function test_the_last_second_of_friday_baghdad_is_still_this_week(): void
    {
        $week = WeekWindow::containing('2026-08-22');

        $this->assertTrue($week->contains(Carbon::parse('2026-08-28 23:59:59', 'Asia/Baghdad')));
        $this->assertFalse($week->contains(Carbon::parse('2026-08-29 00:00:00', 'Asia/Baghdad')));
    }

    private function seedTwoWeeks(): void
    {
        $this->stars = $this->makeGame(['code' => 'STARS', 'name' => 'صيد النجوم']);
        $this->friend = $this->makeGame(['code' => 'FRIEND', 'name' => 'نظرة الصديق']);
        $this->child = $this->makeChild();

        // previous week: one day, one game, 5/10
        $this->travelTo(Carbon::parse('2026-08-18 10:00', 'Asia/Baghdad'));
        $this->play($this->child, $this->stars, 30);

        // this week: stars on Sat/Sun/Mon, friend on Sun
        $this->travelTo(Carbon::parse('2026-08-22 10:00', 'Asia/Baghdad'));
        $this->play($this->child, $this->stars, 30);   // 5
        $this->travelTo(Carbon::parse('2026-08-23 10:00', 'Asia/Baghdad'));
        $this->play($this->child, $this->stars, 54);   // 9 — passed
        $this->play($this->child, $this->friend, 60);  // 10 — passed
        $this->travelTo(Carbon::parse('2026-08-24 10:00', 'Asia/Baghdad'));
        $this->play($this->child, $this->stars, 18);   // 3
    }

    public function test_the_weekly_report_is_the_sum_of_the_days_game_by_game(): void
    {
        $this->seedTwoWeeks();

        $data = $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress/weekly')
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('2026-08-22', $data['week']['start']);
        $this->assertSame('2026-08-28', $data['week']['end']);
        $this->assertTrue($data['week']['is_current']);
        $this->assertSame(3, $data['week']['days_elapsed']);

        $s = $data['summary'];
        $this->assertSame(3, $s['days_active']);
        $this->assertSame(4, $s['attempts']);
        $this->assertSame(2, $s['games_played']);
        $this->assertSame(2, $s['games_passed']);
        $this->assertSame(162, $s['attention_seconds']);
        $this->assertSame(10, $s['best_score']);

        $this->assertCount(7, $data['days']);
        $this->assertSame('2026-08-22', $data['days'][0]['date']);
        $this->assertSame(2, $data['days'][1]['attempts']);
        $this->assertSame(2, $data['days'][1]['games_passed']);
        $this->assertTrue($data['days'][2]['is_today']);
        $this->assertTrue($data['days'][3]['is_future']);

        $stars = collect($data['games'])->firstWhere('game.code', 'STARS');
        $this->assertSame(3, $stars['days_played']);
        $this->assertSame(3, $stars['attempts']);
        $this->assertSame(9, $stars['best_score']);
        $this->assertEquals(5.7, $stars['average_best_score']);   // (5 + 9 + 3) / 3
        $this->assertSame(1, $stars['days_passed']);
        $this->assertSame('PASSED', $stars['status']);
        $this->assertSame(8, $stars['required_score']);
        $this->assertCount(7, $stars['per_day']);
        $this->assertSame(9, $stars['per_day'][1]['best_score']);
        $this->assertTrue($stars['per_day'][1]['is_passed']);

        // against last week: best went from 5 to 9
        $this->assertSame(5, $stars['previous']['best_score']);
        $this->assertEquals(4, $stars['delta']['best_score']);
        $this->assertSame(1, $data['previous_summary']['attempts']);
        $this->assertEquals(3, $data['delta']['attempts']);

        $this->assertSame('الانتباه', $data['axes'][0]['name']);
        $this->assertSame(2, $data['axes'][0]['games_passed']);
    }

    public function test_any_past_week_can_be_asked_for_by_a_date_inside_it(): void
    {
        $this->seedTwoWeeks();

        $data = $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress/weekly?week=2026-08-20')
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('2026-08-15', $data['week']['start']);
        $this->assertFalse($data['week']['is_current']);
        $this->assertSame(7, $data['week']['days_elapsed']);
        $this->assertSame(1, $data['summary']['attempts']);
        $this->assertNull($data['previous_summary']);
        $this->assertNull($data['delta']);
    }

    public function test_a_future_week_is_refused(): void
    {
        $this->seedTwoWeeks();

        $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress/weekly?week=2026-09-05')
            ->assertStatus(422);
    }

    public function test_the_admin_reads_the_same_report(): void
    {
        $this->seedTwoWeeks();
        $admin = User::create(['name' => 'Admin', 'email' => 'w@test.com', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);

        $this->withHeader('Authorization', 'Bearer '.$admin->createToken('a')->plainTextToken)
            ->getJson("/api/v1/admin/subscribers/{$this->child->id}/weekly?week=2026-08-23")
            ->assertStatus(200)
            ->assertJsonPath('data.week.start', '2026-08-22')
            ->assertJsonPath('data.summary.attempts', 4)
            ->assertJsonCount(2, 'data.games');
    }

    public function test_the_monthly_report_is_the_same_sum_over_the_month(): void
    {
        $this->seedTwoWeeks();

        $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress/monthly')
            ->assertStatus(200)
            ->assertJsonPath('data.period', 'monthly')
            ->assertJsonPath('data.start_date', '2026-08-01')
            ->assertJsonPath('data.attempts', 5)
            ->assertJsonPath('data.days_active', 4);
    }
}
