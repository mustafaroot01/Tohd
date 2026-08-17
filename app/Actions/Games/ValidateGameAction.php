<?php

namespace App\Actions\Games;

use App\Enums\GameType;
use App\Exceptions\InvalidGameConfigurationException;
use App\Models\Game;

class ValidateGameAction
{
    /**
     * Validate that a game is structurally complete and ready for approval/publishing.
     *
     * @throws InvalidGameConfigurationException
     */
    public function execute(Game $game): bool
    {
        if (! $game->axis_id || ! $game->axis) {
            throw new InvalidGameConfigurationException('يجب ربط اللعبة بمحور تدريبي صالح.');
        }

        if (! $game->skill_id || ! $game->skill) {
            throw new InvalidGameConfigurationException('يجب ربط اللعبة بمهارة تدريبية صالحة.');
        }

        if ($game->skill->axis_id !== $game->axis_id) {
            throw new InvalidGameConfigurationException('المهارة المحددة لا تنتمي إلى المحور التدريبي المختار.');
        }

        if (! ($game->type instanceof GameType)) {
            throw new InvalidGameConfigurationException('نوع اللعبة غير صالح.');
        }

        $config = $game->config ?? [];
        $attempts = $config['attempts'] ?? 0;
        $threshold = $config['success_threshold'] ?? 0;

        if ($attempts < 1) {
            throw new InvalidGameConfigurationException('إعدادات اللعبة تفتقر إلى عدد محاولات صالح (attempts >= 1).');
        }

        if ($threshold < 0.1 || $threshold > 1.0) {
            throw new InvalidGameConfigurationException('معيار النجاح يجب أن يكون قيمة عشرية بين 0.1 و 1.0.');
        }

        return true;
    }
}
