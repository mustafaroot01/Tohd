<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignUuid('curriculum_day_id')->nullable()->constrained('curriculum_days')->nullOnDelete();
            $table->dateTime('started_at')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->integer('duration_seconds')->default(0);
            $table->integer('attempts')->default(0);
            $table->integer('correct_attempts')->default(0);
            $table->integer('incorrect_attempts')->default(0);
            $table->integer('score')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0.00);
            $table->string('status', 20)->default('STARTED')->index(); // STARTED, COMPLETED, ABANDONED, FAILED
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'game_id', 'status']);
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('user_skill_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->integer('games_completed')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->unsignedBigInteger('total_duration_seconds')->default(0);
            $table->decimal('average_accuracy', 5, 2)->default(0.00);
            $table->integer('best_score')->default(0);
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_skill_progress');
        Schema::dropIfExists('game_sessions');
    }
};
