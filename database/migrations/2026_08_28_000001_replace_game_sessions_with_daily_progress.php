<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No more row per attempt.
 *
 * An attempt lives in a signed token between "start" and "complete"; the
 * result is folded into two places and nowhere else:
 *
 *   subscriber_game_progress  — one row per (child, game): where the child
 *                               stands now. Every hot read uses this.
 *   subscriber_game_daily     — one row per (child, day, game): the history,
 *                               at the resolution a specialist evaluates at.
 *                               Weekly and monthly reports are sums over it.
 *
 * Cost per attempt is therefore two row updates, and a child who plays forty
 * times a day costs exactly what a child who plays once does. game_sessions
 * (18 M rows a year at 10 000 children) and user_skill_progress (written,
 * never read) go.
 *
 * Every new time column is DATETIME, not TIMESTAMP: the app clock is Baghdad
 * and the connection pins MySQL to +03:00, so nothing is ever converted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('user_skill_progress');

        // MySQL DDL is not transactional: every step below checks before it
        // acts, so an interrupted run can simply be run again.
        Schema::table('subscribers', function (Blueprint $table): void {
            // Replay watermark and attention floor, in epoch microseconds.
            // Integers on purpose: a DATETIME(6) written through Eloquent loses
            // its fraction, and a truncated watermark lets a token be redeemed
            // twice. Both are per child, not per game, because a child cannot
            // watch two games at once — and so a board rebuild cannot reset them.
            if (! Schema::hasColumn('subscribers', 'last_attempt_started_us')) {
                $table->unsignedBigInteger('last_attempt_started_us')->nullable()->after('last_activity_at');
                $table->unsignedBigInteger('last_attempt_completed_us')->nullable()->after('last_attempt_started_us');
            }
            // which token the watermark belongs to — two tokens issued in the same
            // microsecond for different games must not be mistaken for a replay
            if (! Schema::hasColumn('subscribers', 'last_attempt_nonce')) {
                $table->char('last_attempt_nonce', 16)->nullable()->after('last_attempt_completed_us');
            }
        });

        Schema::table('subscriber_game_progress', function (Blueprint $table): void {
            $table->unsignedInteger('today_attempts')->default(0)->change();
            $table->unsignedInteger('today_failed')->default(0)->change();
            if (! Schema::hasColumn('subscriber_game_progress', 'today_short')) {
                $table->unsignedInteger('today_short')->default(0)->after('today_failed');
                $table->boolean('last_passed')->default(false)->after('last_seconds');
                $table->unsignedInteger('total_short')->default(0)->after('total_passed');
            }

            $table->dateTime('passed_at')->nullable()->change();
            $table->dateTime('skipped_at')->nullable()->change();
            $table->dateTime('last_played_at')->nullable()->change();

            // passed_at is only ever filtered in PHP; the unique key serves every lookup
            if (Schema::hasIndex('subscriber_game_progress', 'subscriber_game_progress_subscriber_id_passed_at_index')) {
                $table->dropIndex('subscriber_game_progress_subscriber_id_passed_at_index');
            }
        });

        if (Schema::hasTable('subscriber_game_daily')) {
            return;
        }

        Schema::create('subscriber_game_daily', function (Blueprint $table): void {
            $table->char('subscriber_id', 36);
            $table->date('date');
            $table->char('game_id', 36);
            $table->char('curriculum_day_id', 36)->nullable();

            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('failed')->default(0);
            // attempts too short to count as a real try — the abandonment signal
            $table->unsignedInteger('short')->default(0);

            $table->unsignedTinyInteger('best_score')->nullable();
            $table->unsignedInteger('best_seconds')->default(0);
            $table->unsignedTinyInteger('last_score')->nullable();
            $table->unsignedInteger('last_seconds')->default(0);
            $table->boolean('last_passed')->default(false);
            $table->unsignedInteger('score_sum')->default(0);
            $table->unsignedInteger('attention_seconds')->default(0);

            // frozen when the row is opened: lowering a game's bar next month
            // must not rewrite what "passed" meant on this day
            $table->unsignedTinyInteger('required_score');
            $table->unsignedInteger('required_seconds');

            $table->dateTime('passed_at')->nullable();
            $table->dateTime('skipped_at')->nullable();
            $table->dateTime('first_played_at')->nullable();
            $table->dateTime('last_played_at')->nullable();

            // (child, date, game): a child's week is one contiguous range
            $table->primary(['subscriber_id', 'date', 'game_id']);
            $table->index(['game_id', 'date']);
            // the admin dashboard asks "what happened today, across every child"
            $table->index(['date', 'subscriber_id']);

            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
            $table->foreign('game_id')->references('id')->on('games')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_game_daily');

        Schema::table('subscriber_game_progress', function (Blueprint $table): void {
            foreach (['today_short', 'last_passed', 'total_short'] as $column) {
                if (Schema::hasColumn('subscriber_game_progress', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (! Schema::hasIndex('subscriber_game_progress', 'subscriber_game_progress_subscriber_id_passed_at_index')) {
                $table->index(['subscriber_id', 'passed_at']);
            }
        });

        Schema::table('subscribers', function (Blueprint $table): void {
            foreach (['last_attempt_started_us', 'last_attempt_completed_us', 'last_attempt_nonce'] as $column) {
                if (Schema::hasColumn('subscribers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // game_sessions and user_skill_progress are not recreated: their
        // contents cannot be reconstructed, and nothing reads them any more.
    }
};
