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
        Schema::create('curriculums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, PUBLISHED, ARCHIVED
            $table->integer('version')->default(1);
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_months', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->integer('month_number')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['curriculum_id', 'month_number']);
        });

        Schema::create('curriculum_weeks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curriculum_month_id')->constrained('curriculum_months')->cascadeOnDelete();
            $table->integer('week_number')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['curriculum_month_id', 'week_number']);
        });

        Schema::create('curriculum_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curriculum_week_id')->constrained('curriculum_weeks')->cascadeOnDelete();
            $table->integer('day_number')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('estimated_duration_seconds')->default(900);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['curriculum_week_id', 'day_number']);
        });

        Schema::create('curriculum_day_games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curriculum_day_id')->constrained('curriculum_days')->cascadeOnDelete();
            $table->foreignUuid('game_id')->constrained('games')->cascadeOnDelete();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_required')->default(true);
            $table->json('config_override')->nullable();
            $table->timestamps();

            $table->unique(['curriculum_day_id', 'game_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_day_games');
        Schema::dropIfExists('curriculum_days');
        Schema::dropIfExists('curriculum_weeks');
        Schema::dropIfExists('curriculum_months');
        Schema::dropIfExists('curriculums');
    }
};
