<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreGovernorateRequest;
use App\Http\Requests\Api\V1\Admin\UpdateGovernorateRequest;
use App\Http\Resources\Api\V1\GovernorateResource;
use App\Models\Governorate;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GovernorateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Governorate::withCount('profiles');

        TableQuery::filters($query, $request, ['is_active']);

        TableQuery::search($query, $request, ['name', 'code']);

        TableQuery::sort($query, $request, ['name', 'code', 'sort_order', 'created_at'], 'sort_order', 'asc');

        $governorates = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: GovernorateResource::collection($governorates),
            message: 'تم استرجاع قائمة المحافظات بنجاح',
            meta: TableQuery::meta($governorates)
        );
    }

    public function store(StoreGovernorateRequest $request): JsonResponse
    {
        $governorate = Governorate::create($request->validated());

        return ApiResponse::success(
            data: new GovernorateResource($governorate),
            message: 'تمت إضافة المحافظة بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Governorate $governorate): JsonResponse
    {
        return ApiResponse::success(
            data: new GovernorateResource($governorate->loadCount('profiles')),
            message: 'تم استرجاع بيانات المحافظة بنجاح'
        );
    }

    public function update(UpdateGovernorateRequest $request, Governorate $governorate): JsonResponse
    {
        $governorate->update($request->validated());

        return ApiResponse::success(
            data: new GovernorateResource($governorate->fresh()->loadCount('profiles')),
            message: 'تم تحديث بيانات المحافظة بنجاح'
        );
    }

    /**
     * A governorate in use is hidden, never deleted — deleting it would strip
     * the answer from every account that chose it.
     */
    public function destroy(Governorate $governorate): JsonResponse
    {
        if ($governorate->profiles()->exists()) {
            return ApiResponse::error(
                message: 'لا يمكن حذف هذه المحافظة لأنها مستخدمة في بيانات مشتركين، يمكنك إخفاؤها بدلاً من ذلك.',
                errorCode: 'GOVERNORATE_IN_USE',
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $governorate->delete();

        return ApiResponse::success(
            message: 'تم حذف المحافظة بنجاح'
        );
    }
}
