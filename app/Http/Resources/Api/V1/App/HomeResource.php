<?php

namespace App\Http\Resources\Api\V1\App;

use App\Http\Resources\Api\V1\ActivationCodeResource;
use App\Http\Resources\Api\V1\GameSessionResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'activation' => $this->resource['activation'] ? new ActivationCodeResource($this->resource['activation']) : null,
            'assignment' => $this->resource['assignment'] ? [
                'id' => $this->resource['assignment']->id,
                'starts_at' => $this->resource['assignment']->starts_at?->toISOString(),
                'ends_at' => $this->resource['assignment']->ends_at?->toISOString(),
                'days_remaining' => max(0, (int) now()->diffInDays($this->resource['assignment']->ends_at, false)),
            ] : null,
            'today' => $this->resource['today'],
            'progress' => $this->resource['progress'],
            'continue_session' => $this->resource['continue_session'] ? new GameSessionResource($this->resource['continue_session']) : null,
        ];
    }
}
