<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every foreign key in the schema had a real constraint but no index behind it,
 * so any lookup or join on those columns was a full table scan — confirmed with
 * EXPLAIN QUERY PLAN on skills.axis_id, games.axis_id, otps.subscriber_id and
 * seven others. Cascading and RESTRICT deletes scan too.
 *
 * Columns already led by a composite index are deliberately left alone.
 */
return new class extends Migration
{
    /**
     * table => [foreign key columns needing an index]
     *
     * @var array<string, array<int, string>>
     */
    private array $indexes = [
        'skills' => ['axis_id'],
        'assets' => ['uploaded_by'],
        'game_assets' => ['asset_id'],
        'curriculums' => ['created_by', 'updated_by'],
        'curriculum_day_games' => ['game_id'],
        'products' => ['curriculum_id'],
        'audit_logs' => ['user_id'],
        'activation_codes' => ['product_id', 'activated_by'],
        'user_curriculum_assignments' => ['curriculum_id', 'activation_id'],
        'game_sessions' => ['game_id', 'curriculum_day_id'],
        'user_skill_progress' => ['skill_id'],
        'otps' => ['subscriber_id'],
        'games' => ['axis_id', 'skill_id', 'created_by', 'updated_by'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    $blueprint->index($column, "{$table}_{$column}_index");
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    $blueprint->dropIndex("{$table}_{$column}_index");
                }
            });
        }
    }
};
