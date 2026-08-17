<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UploadAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxLottie = config('media.max_lottie_size_kb', 10240);

        return [
            'file' => ['required', 'file', "max:{$maxLottie}"],
            'type' => ['required', new Enum(AssetType::class)],
            'code' => ['nullable', 'string', 'max:50', 'unique:assets,code'],
            'name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
