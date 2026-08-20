<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\GameType;
use App\Rules\ValidGameConfigRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:games,code'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:games,slug'],
            'description' => ['nullable', 'string'],
            'type' => ['required', new Enum(GameType::class)],
            'axis_id' => ['required', 'uuid', 'exists:axes,id'],
            'skill_id' => ['required', 'uuid', 'exists:skills,id'],
            'level_id' => ['required', 'uuid', 'exists:levels,id'],
            'level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
            'min_age' => ['nullable', 'integer', 'min:1', 'max:18'],
            'max_age' => ['nullable', 'integer', 'min:1', 'max:18'],
            'duration_seconds' => ['nullable', 'integer', 'min:10', 'max:3600'],
            'config' => ['nullable', 'array', new ValidGameConfigRule],
            'assets' => ['nullable', 'array'],
            'assets.*.asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'assets.*.role' => ['nullable', 'string'],
            'assets.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
