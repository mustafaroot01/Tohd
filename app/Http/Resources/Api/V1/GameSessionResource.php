<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'game_id' => $this->game_id,
            'game_name' => $this->game?->name,
            'game_code' => $this->game?->code,
            'curriculum_day_id' => $this->curriculum_day_id,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'attempts' => $this->attempts,
            'correct_attempts' => $this->correct_attempts,
            'incorrect_attempts' => $this->incorrect_attempts,
            'score' => $this->score,
            'accuracy' => (float) $this->accuracy,
            'status' => $this->status?->value ?? (string) $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
