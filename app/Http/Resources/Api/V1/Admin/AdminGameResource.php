<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\AssetResource;
use App\Http\Resources\Api\V1\AxisResource;
use App\Http\Resources\Api\V1\LevelResource;
use App\Http\Resources\Api\V1\SkillResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Concerns\SerializesEnums;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGameResource extends JsonResource
{
    use SerializesEnums;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->enumValue($this->type),
            'axis_id' => $this->axis_id,
            'skill_id' => $this->skill_id,
            'level_id' => $this->level_id,
            'axis' => new AxisResource($this->whenLoaded('axis')),
            'skill' => new SkillResource($this->whenLoaded('skill')),
            'game_level' => new LevelResource($this->whenLoaded('gameLevel')),
            'level' => $this->level,
            'difficulty' => $this->difficulty,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'duration_seconds' => $this->duration_seconds,
            'status' => $this->enumValue($this->status),
            'version' => $this->version,
            'config' => $this->config,
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'published_at' => $this->published_at?->toISOString(),
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'updated_by' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
