<?php

namespace App\Models;

use App\Enums\AssetRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAsset extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'game_assets';

    protected $fillable = [
        'game_id',
        'asset_id',
        'role',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role' => AssetRole::class,
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
