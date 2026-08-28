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
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->dropForeign(['activated_by']);
        });
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->foreign('activated_by')->references('id')->on('subscribers')->nullOnDelete();
        });

        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->renameColumn('user_id', 'subscriber_id');
        });
        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->renameColumn('user_id', 'subscriber_id');
        });
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
        });

        Schema::table('user_skill_progress', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('user_skill_progress', function (Blueprint $table) {
            $table->renameColumn('user_id', 'subscriber_id');
        });
        Schema::table('user_skill_progress', function (Blueprint $table) {
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_skill_progress')) {
            Schema::table('user_skill_progress', function (Blueprint $table) {
                $table->dropForeign(['subscriber_id']);
            });
        }
        if (Schema::hasTable('user_skill_progress')) {
            Schema::table('user_skill_progress', function (Blueprint $table) {
                $table->renameColumn('subscriber_id', 'user_id');
            });
        }
        if (Schema::hasTable('user_skill_progress')) {
            Schema::table('user_skill_progress', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('game_sessions')) {
            Schema::table('game_sessions', function (Blueprint $table) {
                $table->dropForeign(['subscriber_id']);
            });
        }
        if (Schema::hasTable('game_sessions')) {
            Schema::table('game_sessions', function (Blueprint $table) {
                $table->renameColumn('subscriber_id', 'user_id');
            });
        }
        if (Schema::hasTable('game_sessions')) {
            Schema::table('game_sessions', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->dropForeign(['subscriber_id']);
        });
        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->renameColumn('subscriber_id', 'user_id');
        });
        Schema::table('user_curriculum_assignments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('activation_codes', function (Blueprint $table) {
            $table->dropForeign(['activated_by']);
        });
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->foreign('activated_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
