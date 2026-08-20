<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level_number' => $this->level_number,
            'name' => $this->name,
            'description' => $this->description,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'games_count' => $this->whenCounted('games'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
