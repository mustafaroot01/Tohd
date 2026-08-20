<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'level_number',
        'name',
        'description',
        'min_age',
        'max_age',
    ];

    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
            'min_age' => 'integer',
            'max_age' => 'integer',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
