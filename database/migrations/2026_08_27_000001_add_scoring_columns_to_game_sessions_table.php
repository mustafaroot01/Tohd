<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The grade is frozen onto the session when it completes.
 *
 * Recomputing it later from the game's current config would rewrite history:
 * lowering "معيار النجاح" next month would silently flip last month's failures
 * into passes. What the child achieved under the rules of the day stays put.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->unsignedInteger('effective_seconds')->default(0)->after('duration_seconds');
            $table->unsignedTinyInteger('score_out_of_ten')->nullable()->after('accuracy');
            $table->boolean('is_passed')->default(false)->after('score_out_of_ten');
            $table->unsignedSmallInteger('required_seconds')->nullable()->after('is_passed');
            $table->decimal('applied_threshold', 3, 2)->nullable()->after('required_seconds');

            $table->index(['subscriber_id', 'game_id', 'is_passed'], 'game_sessions_scoring_index');
        });
    }

    public function down(): void
    {
        // superseded: a later migration drops game_sessions altogether
        if (! Schema::hasTable('game_sessions')) {
            return;
        }

        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->dropIndex('game_sessions_scoring_index');
            $table->dropColumn([
                'effective_seconds',
                'score_out_of_ten',
                'is_passed',
                'required_seconds',
                'applied_threshold',
            ]);
        });
    }
};
