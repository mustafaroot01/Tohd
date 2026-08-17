<?php

namespace App\Actions\Games;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\GameConfigurationBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateGameAction
{
    public function __construct(
        protected GameConfigurationBuilder $configBuilder,
        protected AuditLogService $auditLog
    ) {}

    /**
     * Create a new game with standardized configuration and optional assets.
     */
    public function execute(array $data, ?User $creator = null): Game
    {
        return DB::transaction(function () use ($data, $creator) {
            $config = $this->configBuilder->build($data['type'], $data['config'] ?? []);

            $game = Game::create([
                'code' => $data['code'] ?? 'GAM-'.strtoupper(Str::random(6)),
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4),
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'axis_id' => $data['axis_id'],
                'skill_id' => $data['skill_id'],
                'level' => $data['level'] ?? 1,
                'difficulty' => $data['difficulty'] ?? 'easy',
                'min_age' => $data['min_age'] ?? 3,
                'max_age' => $data['max_age'] ?? 12,
                'duration_seconds' => $data['duration_seconds'] ?? 60,
                'status' => GameStatus::DRAFT,
                'version' => 1,
                'config' => $config,
                'created_by' => $creator?->id,
                'updated_by' => $creator?->id,
            ]);

            if (! empty($data['assets']) && is_array($data['assets'])) {
                foreach ($data['assets'] as $assetData) {
                    $game->gameAssets()->create([
                        'asset_id' => $assetData['asset_id'],
                        'role' => $assetData['role'] ?? 'ANIMATION',
                        'sort_order' => $assetData['sort_order'] ?? 0,
                        'metadata' => $assetData['metadata'] ?? null,
                    ]);
                }
            }

            $this->auditLog->log('GAME_CREATED', 'Game', $game->id, null, $game->toArray(), $creator);

            return $game->fresh(['axis', 'skill', 'assets']);
        });
    }
}
