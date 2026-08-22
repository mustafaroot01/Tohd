<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateAssetRequest;
use App\Http\Requests\Api\V1\Admin\UploadAssetRequest;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Services\AssetStorageService;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Asset::query();

        TableQuery::filters($query, $request, ['type', 'status']);

        TableQuery::search($query, $request, ['name', 'code']);

        TableQuery::sort($query, $request, ['name', 'code', 'type', 'size', 'status', 'created_at'], 'created_at');

        $assets = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: AssetResource::collection($assets),
            message: 'تم استرجاع قائمة الوسائط بنجاح',
            meta: TableQuery::meta($assets)
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
