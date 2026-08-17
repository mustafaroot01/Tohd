<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\GameType;
use App\Rules\ValidGameConfigRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gameId = $this->route('game')?->id ?? $this->route('game');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:games,slug,'.$gameId],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'required', new Enum(GameType::class)],
            'axis_id' => ['sometimes', 'required', 'uuid', 'exists:axes,id'],
            'skill_id' => ['sometimes', 'required', 'uuid', 'exists:skills,id'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['sometimes', 'string', 'in:easy,medium,hard'],
            'min_age' => ['sometimes', 'integer', 'min:1', 'max:18'],
            'max_age' => ['sometimes', 'integer', 'min:1', 'max:18'],
            'duration_seconds' => ['sometimes', 'integer', 'min:10', 'max:3600'],
            'config' => ['sometimes', 'nullable', 'array', new ValidGameConfigRule],
            'assets' => ['nullable', 'array'],
            'assets.*.asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'assets.*.role' => ['nullable', 'string'],
            'assets.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
