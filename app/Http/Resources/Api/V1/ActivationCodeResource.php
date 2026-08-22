<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Concerns\SerializesEnums;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivationCodeResource extends JsonResource
{
    use SerializesEnums;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'status' => $this->enumValue($this->status),
            'activated_by' => $this->activated_by,
            'activated_user' => new SubscriberResource($this->whenLoaded('user')),
            'activated_at' => $this->activated_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
