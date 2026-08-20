<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Assets\UploadAssetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UploadAssetRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAssetRequest;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Services\AssetStorageService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Asset::latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $assets = $query->paginate($perPage);

        return ApiResponse::success(
            data: AssetResource::collection($assets),
            message: 'تم استرجاع قائمة الوسائط بنجاح',
            meta: [
                'current_page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ]
        );
    }

    public function store(UploadAssetRequest $request, AssetStorageService $storageService): JsonResponse
    {
        $asset = $storageService->storeAsset(
            file: $request->file('file'),
            data: $request->validated(),
            uploader: $request->user()
        );

        return ApiResponse::success(
            data: new AssetResource($asset),
            message: 'تم رفع الملف بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Asset $asset): JsonResponse
    {
        return ApiResponse::success(
            data: new AssetResource($asset),
            message: 'تم استرجاع بيانات الملف بنجاح'
        );
    }

    public function update(UpdateAssetRequest $request, Asset $asset, AssetStorageService $storageService): JsonResponse
    {
        $updated = $storageService->updateAsset(
            asset: $asset,
            data: $request->validated(),
            file: $request->file('file')
        );

        return ApiResponse::success(
            data: new AssetResource($updated),
            message: 'تم تحديث بيانات الملف بنجاح'
        );
    }

    public function destroy(Asset $asset, AssetStorageService $storageService): JsonResponse
    {
        $storageService->deleteAsset($asset);

        return ApiResponse::success(
            data: null,
            message: 'تم حذف الملف بنجاح'
        );
    }
}

