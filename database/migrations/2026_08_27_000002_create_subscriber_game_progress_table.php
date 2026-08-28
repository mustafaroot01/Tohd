<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (child, game): the child's standing on that game, kept current
 * by the same transaction that records each session.
 *
 * game_sessions is an append-only event log and grows without bound. Every hot
 * read used to re-scan it — measured at 872ms and 34MB for a child with 10,000
 * sessions, and a fatal out-of-memory at 30,000. This projection makes read
 * cost a function of the game catalogue, not of how long the child has played.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriber_game_progress', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->foreignUuid('game_id')->constrained('games')->cascadeOnDelete();

            // all-time
            $table->unsignedTinyInteger('best_score')->nullable();
            $table->unsignedInteger('best_seconds')->default(0);
            $table->timestamp('passed_at')->nullable();

            // today — reset on the first write of a new calendar day
            $table->date('today_date')->nullable();
            $table->unsignedTinyInteger('today_best_score')->nullable();
            $table->unsignedInteger('today_best_seconds')->default(0);
            $table->unsignedSmallInteger('today_attempts')->default(0);
            $table->unsignedSmallInteger('today_failed')->default(0);
            $table->timestamp('skipped_at')->nullable();

            // most recent
            $table->unsignedTinyInteger('last_score')->nullable();
            $table->unsignedInteger('last_seconds')->default(0);
            $table->timestamp('last_played_at')->nullable();

            // totals for reports
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('total_passed')->default(0);
            $table->unsignedInteger('total_score_sum')->default(0);
            $table->unsignedBigInteger('total_attention_seconds')->default(0);

            $table->timestamps();

            $table->unique(['subscriber_id', 'game_id']);
            $table->index(['subscriber_id', 'passed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_game_progress');
    }
};
