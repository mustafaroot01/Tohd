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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('type', 30)->index(); // LOTTIE, IMAGE, AUDIO, VIDEO, BACKGROUND, CHARACTER, OBJECT, OTHER
            $table->string('mime_type', 100);
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('ACTIVE')->index();
            $table->integer('version')->default(1);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
