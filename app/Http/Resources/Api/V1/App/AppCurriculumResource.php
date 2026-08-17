<?php

namespace App\Http\Resources\Api\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppCurriculumResource extends JsonResource
{
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
