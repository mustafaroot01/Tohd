<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type?->value ?? (string) $this->type,
            'mime_type' => $this->mime_type,
            'url' => $this->url,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'metadata' => $this->metadata,
            'status' => $this->status?->value ?? (string) $this->status,
            'version' => $this->version,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
