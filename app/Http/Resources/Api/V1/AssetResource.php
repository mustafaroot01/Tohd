<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AssetRole;
use App\Http\Resources\Concerns\SerializesEnums;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    use SerializesEnums;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->enumValue($this->type),
            'mime_type' => $this->mime_type,
            'url' => $this->url,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'metadata' => $this->metadata,
            // When the asset arrives through a game's pivot, carry the role it
            // plays in that game — the dashboard needs it to tell the start
            // sound from the end sound.
            'role' => $this->whenPivotLoaded('game_assets', fn () => $this->enumValue($this->pivot->role)),
            'role_label' => $this->whenPivotLoaded('game_assets', function () {
                // the belongsToMany pivot hands back a plain string, not the enum
                $role = $this->pivot->role;

                if ($role instanceof AssetRole) {
                    return $role->label();
                }

                return $role ? (AssetRole::tryFrom((string) $role)?->label() ?? (string) $role) : null;
            }),
            'status' => $this->enumValue($this->status),
            'version' => $this->version,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
