<?php

namespace App\Services;

use App\Exceptions\InvalidLottieFileException;
use Illuminate\Http\UploadedFile;

class LottieValidationService
{
    /**
     * Validate Lottie JSON file or content and extract metadata.
     *
     * @return array{metadata: array, checksum: string, size: int}
     *
     * @throws InvalidLottieFileException
     */
    public function validateAndExtract(UploadedFile|string $file): array
    {
        $content = $file instanceof UploadedFile ? $file->get() : $file;

        if (empty($content)) {
            throw new InvalidLottieFileException('محتوى ملف Lottie فارغ');
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new InvalidLottieFileException('الملف المرفق ليس بصيغة JSON صالحة: '.json_last_error_msg());
        }

        $requiredKeys = config('media.lottie_required_keys', ['v', 'fr', 'ip', 'op', 'w', 'h', 'layers']);

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw new InvalidLottieFileException("ملف Lottie يفتقر إلى الحقل المطلوب: {$key}");
            }
        }

        $fps = (float) ($decoded['fr'] ?? 30);
        $ip = (float) ($decoded['ip'] ?? 0);
        $op = (float) ($decoded['op'] ?? 0);
        $frames = max(0, $op - $ip);
        $durationSeconds = $fps > 0 ? round($frames / $fps, 2) : 0;

        $metadata = [
            'lottie_version' => (string) ($decoded['v'] ?? '1.0.0'),
            'width' => (int) ($decoded['w'] ?? 0),
            'height' => (int) ($decoded['h'] ?? 0),
            'frame_rate' => $fps,
            'in_point' => $ip,
            'out_point' => $op,
            'total_frames' => (int) $frames,
            'duration_seconds' => $durationSeconds,
            'layers_count' => count($decoded['layers'] ?? []),
            'has_markers' => ! empty($decoded['markers']),
        ];

        return [
            'metadata' => $metadata,
            'checksum' => hash('sha256', $content),
            'size' => strlen($content),
        ];
    }
}
