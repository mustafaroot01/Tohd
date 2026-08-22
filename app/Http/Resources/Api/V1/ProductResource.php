<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Concerns\SerializesEnums;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'curriculum_id' => $this->curriculum_id,
            'curriculum_name' => $this->curriculum?->name,
            'duration_days' => $this->duration_days,
            'status' => $this->enumValue($this->status),
            'price' => (float) $this->price,
            'currency' => Product::CURRENCY,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
