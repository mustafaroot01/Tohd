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
        $type = (string) $this->input('type');

        return [
            'file' => array_filter([
                'required',
                'file',
                'max:'.self::maxSizeKbFor($type),
                'extensions:'.implode(',', self::extensionsFor($type)),
                // second gate: what the bytes actually are, not what the name claims
                ($mimes = self::mimeTypesFor($type)) ? 'mimetypes:'.implode(',', $mimes) : null,
            ]),
            'type' => ['required', new Enum(AssetType::class)],
            'code' => ['nullable', 'string', 'max:50', 'unique:assets,code'],
            'name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Per-type size ceiling, from config/media.php.
     *
     * Every type used to share the Lottie ceiling, so audio was refused at 10MB
     * despite max_audio_size_kb being 20MB, and video at 10MB despite 100MB.
     */
    private static function maxSizeKbFor(string $type): int
    {
        return (int) match ($type) {
            AssetType::IMAGE->value, AssetType::BACKGROUND->value,
            AssetType::CHARACTER->value, AssetType::OBJECT->value => config('media.max_image_size_kb', 5120),
            AssetType::AUDIO->value => config('media.max_audio_size_kb', 20480),
            AssetType::VIDEO->value => config('media.max_video_size_kb', 102400),
            default => config('media.max_lottie_size_kb', 10240),
        };
    }

    /**
     * Allowed extensions per type. Uploads used to accept any file at all — a
     * renamed executable included — and land it on the web-servable disk.
     *
     * @return array<int, string>
     */
    private static function extensionsFor(string $type): array
    {
        return match ($type) {
            AssetType::LOTTIE->value => ['json'],
            AssetType::IMAGE->value, AssetType::BACKGROUND->value,
            AssetType::CHARACTER->value, AssetType::OBJECT->value => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            AssetType::AUDIO->value => ['mp3', 'wav', 'wave', 'ogg', 'oga', 'm4a', 'aac', 'flac'],
            AssetType::VIDEO->value => ['mp4', 'webm', 'mov'],
            default => ['json', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp3', 'wav', 'wave', 'ogg', 'oga', 'm4a', 'aac', 'flac', 'mp4', 'webm', 'mov'],
        };
    }

    /**
     * Content-level allowlist, checked against the bytes via finfo.
     *
     * Lottie is deliberately absent — LottieValidationService parses the JSON
     * and checks its structure, which is a stronger gate than any MIME sniff.
     *
     * @return array<int, string>
     */
    private static function mimeTypesFor(string $type): array
    {
        return match ($type) {
            AssetType::AUDIO->value => [
                'audio/mpeg', 'audio/mp3', 'audio/x-mpeg',
                'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave',
                'audio/ogg', 'application/ogg',
                'audio/mp4', 'audio/x-m4a', 'audio/aac', 'audio/x-aac',
                'audio/flac', 'audio/x-flac',
            ],
            AssetType::VIDEO->value => ['video/mp4', 'video/webm', 'video/quicktime'],
            AssetType::IMAGE->value, AssetType::BACKGROUND->value,
            AssetType::CHARACTER->value, AssetType::OBJECT->value => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            ],
            default => [],
        };
    }
}
