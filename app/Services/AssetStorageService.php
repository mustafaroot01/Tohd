<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetStorageService
{
    public function __construct(
        protected LottieValidationService $lottieValidator
    ) {}

    /**
     * Store an uploaded asset and create its record.
     */
    public function storeAsset(UploadedFile $file, array $data, ?User $uploader = null): Asset
    {
        $type = $data['type'] instanceof AssetType ? $data['type'] : AssetType::from($data['type']);
        $disk = config('media.disk', 'public');
        $directory = 'assets/'.strtolower($type->value).'/'.date('Y/m');

        $metadata = $data['metadata'] ?? [];
        $checksum = null;

        if ($type === AssetType::LOTTIE) {
            $lottieData = $this->lottieValidator->validateAndExtract($file);
            $metadata = array_merge($metadata, $lottieData['metadata']);
            $checksum = $lottieData['checksum'];
        } else {
            $checksum = hash_file('sha256', $file->getRealPath());
        }

        $extension = $file->getClientOriginalExtension() ?: ($type === AssetType::LOTTIE ? 'json' : 'bin');
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $filename, $disk);

        return Asset::create([
            'code' => $data['code'] ?? 'AST-'.strtoupper(Str::random(8)),
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'type' => $type,
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'disk' => $disk,
            'path' => $path,
            'size' => $file->getSize() ?: 0,
            'checksum' => $checksum,
            'metadata' => $metadata,
            'status' => 'ACTIVE',
            'version' => 1,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    /**
     * Update an existing asset and optionally replace its file.
     */
    public function updateAsset(Asset $asset, array $data, ?UploadedFile $file = null): Asset
    {
        $type = isset($data['type']) 
            ? ($data['type'] instanceof AssetType ? $data['type'] : AssetType::from($data['type']))
            : $asset->type;

        $updateData = [
            'name' => $data['name'] ?? $asset->name,
            'code' => $data['code'] ?? $asset->code,
            'type' => $type,
        ];

        if ($file) {
            // Delete old file first
            if (Storage::disk($asset->disk)->exists($asset->path)) {
                Storage::disk($asset->disk)->delete($asset->path);
            }

            $directory = 'assets/'.strtolower($type->value).'/'.date('Y/m');
            $metadata = $data['metadata'] ?? [];
            $checksum = null;

            if ($type === AssetType::LOTTIE) {
                $lottieData = $this->lottieValidator->validateAndExtract($file);
                $metadata = array_merge($metadata, $lottieData['metadata']);
                $checksum = $lottieData['checksum'];
            } else {
                $checksum = hash_file('sha256', $file->getRealPath());
            }

            $extension = $file->getClientOriginalExtension() ?: ($type === AssetType::LOTTIE ? 'json' : 'bin');
            $filename = Str::uuid().'.'.$extension;
            $path = $file->storeAs($directory, $filename, $asset->disk);

            $updateData['path'] = $path;
            $updateData['mime_type'] = $file->getClientMimeType() ?: 'application/octet-stream';
            $updateData['size'] = $file->getSize() ?: 0;
            $updateData['checksum'] = $checksum;
            $updateData['metadata'] = $metadata;
            $updateData['version'] = $asset->version + 1;
        }

        $asset->update($updateData);

        return $asset;
    }

    /**
     * Delete an asset and its file from storage.
     */
    public function deleteAsset(Asset $asset): bool
    {
        if (Storage::disk($asset->disk)->exists($asset->path)) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        return (bool) $asset->delete();
    }
}
