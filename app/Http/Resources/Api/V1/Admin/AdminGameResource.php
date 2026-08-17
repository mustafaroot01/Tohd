<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\AssetResource;
use App\Http\Resources\Api\V1\AxisResource;
use App\Http\Resources\Api\V1\SkillResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type?->value ?? (string) $this->type,
            'axis_id' => $this->axis_id,
            'skill_id' => $this->skill_id,
            'axis' => new AxisResource($this->whenLoaded('axis')),
            'skill' => new SkillResource($this->whenLoaded('skill')),
            'level' => $this->level,
            'difficulty' => $this->difficulty,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'duration_seconds' => $this->duration_seconds,
            'status' => $this->status?->value ?? (string) $this->status,
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
