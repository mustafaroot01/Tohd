<?php

namespace App\Actions\Games;

use App\Models\Game;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\GameConfigurationBuilder;
use Illuminate\Support\Facades\DB;

class UpdateGameAction
{
    public function __construct(
        protected GameConfigurationBuilder $configBuilder,
        protected AuditLogService $auditLog
    ) {}

    /**
     * Update an existing game.
     */
    public function execute(Game $game, array $data, ?User $updater = null): Game
    {
        return DB::transaction(function () use ($game, $data, $updater) {
            $oldValues = $game->toArray();

            $updateData = [];
            foreach (['name', 'slug', 'description', 'axis_id', 'skill_id', 'level', 'difficulty', 'min_age', 'max_age', 'duration_seconds'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($data['type'])) {
                $updateData['type'] = $data['type'];
            }

            if (isset($data['config'])) {
                $type = $data['type'] ?? $game->type;
                $updateData['config'] = $this->configBuilder->build($type, $data['config']);
            }

            $updateData['updated_by'] = $updater?->id;
            $game->update($updateData);

            if (isset($data['assets']) && is_array($data['assets'])) {
                $game->gameAssets()->delete();
                foreach ($data['assets'] as $assetData) {
                    $game->gameAssets()->create([
                        'asset_id' => $assetData['asset_id'],
                        'role' => $assetData['role'] ?? 'ANIMATION',
                        'sort_order' => $assetData['sort_order'] ?? 0,
                        'metadata' => $assetData['metadata'] ?? null,
                    ]);
                }
            }

            $this->auditLog->log('GAME_UPDATED', 'Game', $game->id, $oldValues, $game->fresh()->toArray(), $updater);

            return $game->fresh(['axis', 'skill', 'assets']);
        });
    }
}
