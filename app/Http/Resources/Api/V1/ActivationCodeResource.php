<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivationCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'status' => $this->status?->value ?? (string) $this->status,
            'activated_by' => $this->activated_by,
            'activated_user' => new UserResource($this->whenLoaded('user')),
            'activated_at' => $this->activated_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
