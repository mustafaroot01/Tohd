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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('curriculum_id')->constrained('curriculums')->restrictOnDelete();
            $table->integer('duration_days')->default(30);
            $table->string('status', 20)->default('ACTIVE')->index(); // DRAFT, ACTIVE, INACTIVE, ARCHIVED
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('SAR');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activation_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('status', 20)->default('AVAILABLE')->index(); // AVAILABLE, ACTIVATED, EXPIRED, REVOKED
            $table->foreignUuid('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['code', 'status']);
        });

        Schema::create('user_curriculum_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignUuid('activation_id')->nullable()->constrained('activation_codes')->nullOnDelete();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->string('status', 20)->default('ACTIVE')->index(); // ACTIVE, COMPLETED, EXPIRED, PAUSED
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_curriculum_assignments');
        Schema::dropIfExists('activation_codes');
        Schema::dropIfExists('products');
    }
};
