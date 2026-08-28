<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PlaysGames;
use Tests\TestCase;

/**
 * The attention grade reaches every report: the child's own progress endpoints
 * and the admin's subscriber detail.
 */
class GradeReportTest extends TestCase
{
    use PlaysGames, RefreshDatabase;

    protected Subscriber $child;

    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = $this->makeGame();
        $this->child = $this->makeChild();

        $this->play($this->child, $this->game, 54); // 9/10 pass
        $this->play($this->child, $this->game, 30); // 5/10 fail
    }

    public function test_overall_progress_carries_the_grade_summary(): void
    {
        $data = $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress')
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(2, $data['total_attempts']);
        $this->assertSame(1, $data['games_played']);
        $this->assertSame(1, $data['games_passed']);
        $this->assertSame(84, $data['attention_seconds']);

        $grades = $data['grades'];
        $this->assertSame(2, $grades['graded_attempts']);
        $this->assertEquals(7.0, $grades['average_score']);
        $this->assertSame(9, $grades['best_score']);
        $this->assertSame(1, $grades['passed_attempts']);
        $this->assertEquals(50.0, $grades['pass_rate']);
        $this->assertSame(1, $grades['games_passed']);
        $this->assertSame(84, $grades['attention_seconds']);
    }

    public function test_axis_and_skill_breakdowns_carry_grades(): void
    {
        $data = $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress')
            ->json('data');

        $this->assertSame(9, $data['axes_progress'][0]['grades']['best_score']);
        $this->assertSame(9, $data['skills_progress'][0]['grades']['best_score']);
    }

    public function test_daily_report_is_summed_from_the_days_rows(): void
    {
        $this->withHeaders($this->bearer($this->child))
            ->getJson('/api/v1/app/progress/daily')
            ->assertStatus(200)
            ->assertJsonPath('data.period', 'daily')
            ->assertJsonPath('data.attempts', 2)
            ->assertJsonPath('data.games_passed', 1)
            ->assertJsonPath('data.best_score', 9)
            ->assertJsonPath('data.days_active', 1);
    }

    public function test_a_child_with_no_graded_attempts_gets_nulls_not_zeros(): void
    {
        $fresh = $this->makeChild();

        $grades = $this->withHeaders($this->bearer($fresh))
            ->getJson('/api/v1/app/progress')
            ->json('data.grades');

        $this->assertNull($grades['average_score']);
        $this->assertNull($grades['best_score']);
        $this->assertNull($grades['pass_rate']);
    }

    public function test_admin_subscriber_detail_exposes_grades_the_play_summary_and_this_week(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'g@test.com', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);

        $res = $this->withHeader('Authorization', 'Bearer '.$admin->createToken('a')->plainTextToken)
            ->getJson("/api/v1/admin/subscribers/{$this->child->id}")
            ->assertStatus(200);

        $res->assertJsonPath('data.play_summary.attempts', 2)
            ->assertJsonPath('data.play_summary.games_passed', 1)
            ->assertJsonPath('data.play_summary.skipped_games', 0)
            ->assertJsonPath('data.progress.grades.best_score', 9)
            ->assertJsonPath('data.weekly.summary.attempts', 2)
            ->assertJsonPath('data.weekly.games.0.best_score', 9)
            ->assertJsonPath('data.weekly.games.0.status', 'PASSED');

        $this->assertArrayNotHasKey('sessions', $res->json('data'));
    }
}
