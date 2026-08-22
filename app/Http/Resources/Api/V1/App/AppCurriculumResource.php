<?php

namespace App\Http\Resources\Api\V1\App;

use App\Http\Resources\Concerns\SerializesEnums;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppCurriculumResource extends JsonResource
{
    use SerializesEnums;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'months' => $this->whenLoaded('months', function () {
                return $this->months->map(function ($month) {
                    return [
                        'id' => $month->id,
                        'month_number' => $month->month_number,
                        'name' => $month->name,
                        'weeks' => $month->weeks->map(function ($week) {
                            return [
                                'id' => $week->id,
                                'week_number' => $week->week_number,
                                'name' => $week->name,
                                'days' => $week->days->map(function ($day) {
                                    return [
                                        'id' => $day->id,
                                        'day_number' => $day->day_number,
                                        'name' => $day->name,
                                        'estimated_duration_seconds' => $day->estimated_duration_seconds,
                                        'games_count' => $day->dayGames->count(),
                                        'games' => $day->games->map(function ($game) {
                                            return [
                                                'id' => $game->id,
                                                'code' => $game->code,
                                                'name' => $game->name,
                                                'type' => $this->enumValue($game->type),
                                                'level' => $game->level,
                                                'difficulty' => $game->difficulty,
                                                'duration_seconds' => $game->duration_seconds,
                                                'config' => $game->config,
                                                'sort_order' => $game->pivot?->sort_order,
                                                'is_required' => $game->pivot?->is_required,
                                                'assets' => $game->assets->map(function ($asset) {
                                                    return [
                                                        'id' => $asset->id,
                                                        'name' => $asset->name,
                                                        'type' => $this->enumValue($asset->type),
                                                        'mime_type' => $asset->mime_type,
                                                        'url' => $asset->url,
                                                        'role' => $asset->pivot?->role?->value ?? $asset->pivot?->role,
                                                        'sort_order' => $asset->pivot?->sort_order,
                                                    ];
                                                }),
                                            ];
                                        }),
                                    ];
                                }),
                            ];
                        }),
                    ];
                });
            }),
        ];
    }
}
