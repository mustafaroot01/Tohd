<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreAxisRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAxisRequest;
use App\Http\Resources\Api\V1\AxisResource;
use App\Models\Axis;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AxisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Axis::withCount('skills');

        TableQuery::filters($query, $request, ['status']);

        TableQuery::search($query, $request, ['name', 'slug']);

        TableQuery::sort($query, $request, ['name', 'slug', 'status', 'sort_order', 'created_at'], 'sort_order', 'asc');

        $axes = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: AxisResource::collection($axes),
            message: 'تم استرجاع قائمة المحاور بنجاح',
            meta: TableQuery::meta($axes)
        );
    }

    public function store(StoreAxisRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $axis = Axis::create($data);

        return ApiResponse::success(
            data: new AxisResource($axis),
            message: 'تم إنشاء المحور بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Axis $axis): JsonResponse
    {
        $axis->load('skills');

        return ApiResponse::success(
            data: new AxisResource($axis),
            message: 'تم استرجاع بيانات المحور بنجاح'
        );
    }

    public function update(UpdateAxisRequest $request, Axis $axis): JsonResponse
    {
        $axis->update($request->validated());

        return ApiResponse::success(
            data: new AxisResource($axis),
            message: 'تم تحديث بيانات المحور بنجاح'
        );
    }

    public function destroy(Axis $axis): JsonResponse
    {
        $axis->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف المحور بنجاح'
        );
    }
}
