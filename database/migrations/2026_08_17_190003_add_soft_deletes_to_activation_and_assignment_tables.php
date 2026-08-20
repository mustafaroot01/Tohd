<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Defense-in-depth: no route currently deletes activation codes or
     * subscription assignments, but nothing at the schema level prevented it
     * either — soft deletes make an accidental hard-delete recoverable instead
     * of erasing subscriber history permanently.
     */
    public function up(): void
    {
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
