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
        Schema::create('games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 30)->index(); // TAP, CHOOSE, MATCH, TRACKING, MEMORY, EMOTION, SEQUENCE, DRAG_DROP, ORDER
            $table->foreignUuid('axis_id')->constrained('axes')->restrictOnDelete();
            $table->foreignUuid('skill_id')->constrained('skills')->restrictOnDelete();
            $table->integer('level')->default(1)->index();
            $table->string('difficulty', 20)->default('easy')->index();
            $table->integer('min_age')->default(3);
            $table->integer('max_age')->default(12);
            $table->integer('duration_seconds')->default(60);
            $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, TESTING, APPROVED, PUBLISHED, ARCHIVED
            $table->integer('version')->default(1);
            $table->json('config')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('game_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('role', 30)->default('ANIMATION')->index(); // ANIMATION, BACKGROUND, IMAGE, INSTRUCTION_AUDIO, SUCCESS_AUDIO, ERROR_AUDIO, DEMONSTRATION_VIDEO, OTHER
            $table->integer('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'asset_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_assets');
        Schema::dropIfExists('games');
    }
};
