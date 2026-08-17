<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCurriculumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status?->value ?? (string) $this->status,
            'version' => $this->version,
            'published_at' => $this->published_at?->toISOString(),
            'months_count' => $this->whenCounted('months'),
            'months' => $this->whenLoaded('months', function () {
                return $this->months->map(function ($month) {
                    return [
                        'id' => $month->id,
                        'month_number' => $month->month_number,
                        'name' => $month->name,
                        'description' => $month->description,
                        'weeks' => $month->weeks->map(function ($week) {
                            return [
                                'id' => $week->id,
                                'week_number' => $week->week_number,
                                'name' => $week->name,
                                'description' => $week->description,
                                'days' => $week->days->map(function ($day) {
                                    return [
                                        'id' => $day->id,
                                        'day_number' => $day->day_number,
                                        'name' => $day->name,
                                        'estimated_duration_seconds' => $day->estimated_duration_seconds,
                                        'games' => $day->dayGames->map(function ($dayGame) {
                                            return [
                                                'id' => $dayGame->id,
                                                'game_id' => $dayGame->game_id,
                                                'game_code' => $dayGame->game?->code,
                                                'game_name' => $dayGame->game?->name,
                                                'game_type' => $dayGame->game?->type?->value,
                                                'sort_order' => $dayGame->sort_order,
                                                'is_required' => $dayGame->is_required,
                                                'config_override' => $dayGame->config_override,
                                            ];
                                        }),
                                    ];
                                }),
                            ];
                        }),
                    ];
                });
            }),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
