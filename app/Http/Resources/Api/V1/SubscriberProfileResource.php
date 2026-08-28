<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'governorate_id' => $this->governorate_id,
            'governorate_name' => $this->whenLoaded('governorate', fn () => $this->governorate?->name),
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'age' => $this->age,
            'family_order' => $this->family_order,
            'delivery_type' => $this->delivery_type?->value,
            'delivery_type_label' => $this->delivery_type?->label(),
            'is_complete' => $this->isComplete(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
