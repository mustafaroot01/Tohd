<?php

namespace App\Services;

use App\Enums\GameType;

class GameConfigurationBuilder
{
    /**
     * Build a standardized configuration JSON array for games.
     */
    public function build(GameType|string $type, array $inputConfig = []): array
    {
        $gameType = $type instanceof GameType ? $type : GameType::from($type);

        $defaultAttempts = (int) config('game.default_attempts', 10);
        $defaultThreshold = (float) config('game.default_success_threshold', 0.8);
        $defaultTimeLimit = (int) config('game.default_time_limit_seconds', 60);

        return [
            'interaction' => [
                'type' => strtolower($gameType->value),
                'mode' => $inputConfig['interaction']['mode'] ?? 'standard',
            ],
            'attempts' => (int) ($inputConfig['attempts'] ?? $defaultAttempts),
            'success_threshold' => (float) ($inputConfig['success_threshold'] ?? $defaultThreshold),
            'allow_retry' => (bool) ($inputConfig['allow_retry'] ?? true),
            'time_limit_seconds' => (int) ($inputConfig['time_limit_seconds'] ?? $defaultTimeLimit),
            'reward' => [
                'type' => $inputConfig['reward']['type'] ?? 'star',
                'value' => (int) ($inputConfig['reward']['value'] ?? 1),
            ],
            'layout' => $inputConfig['layout'] ?? [
                'orientation' => 'landscape',
                'theme' => 'default',
            ],
            'elements' => $inputConfig['elements'] ?? [],
            'custom_parameters' => $inputConfig['custom_parameters'] ?? [],
        ];
    }
}
