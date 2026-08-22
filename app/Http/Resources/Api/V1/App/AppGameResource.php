<?php

namespace App\Http\Resources\Api\V1\App;

use App\Http\Resources\Api\V1\AssetResource;
use App\Http\Resources\Concerns\SerializesEnums;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppGameResource extends JsonResource
{
    use SerializesEnums;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->enumValue($this->type),
            'level' => $this->level,
            'difficulty' => $this->difficulty,
            'duration_seconds' => $this->duration_seconds,
            'config' => $this->config,
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'axis' => [
                'id' => $this->axis_id,
                'name' => $this->axis?->name,
            ],
            'skill' => [
                'id' => $this->skill_id,
                'name' => $this->skill?->name,
            ],
        ];
    }
}
