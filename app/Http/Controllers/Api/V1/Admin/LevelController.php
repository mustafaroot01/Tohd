<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreLevelRequest;
use App\Http\Requests\Api\V1\Admin\UpdateLevelRequest;
use App\Http\Resources\Api\V1\LevelResource;
use App\Models\Level;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LevelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Level::withCount('games');

        TableQuery::search($query, $request, ['name', 'description']);

        // Allow fetching all without pagination if requested (used by select inputs)
        if ($request->boolean('all')) {
            return ApiResponse::success(
                data: LevelResource::collection($query->orderBy('level_number')->get()),
                message: 'تم استرجاع قائمة المستويات بنجاح'
            );
        }

        TableQuery::sort($query, $request, ['name', 'level_number', 'min_age', 'max_age', 'created_at'], 'level_number', 'asc');

        $levels = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: LevelResource::collection($levels),
            message: 'تم استرجاع قائمة المستويات بنجاح',
            meta: TableQuery::meta($levels)
        );
    }

    public function store(StoreLevelRequest $request): JsonResponse
    {
        $level = Level::create($request->validated());

        return ApiResponse::success(
            data: new LevelResource($level),
            message: 'تم إنشاء المستوى بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Level $level): JsonResponse
    {
        return ApiResponse::success(
            data: new LevelResource($level->loadCount('games')),
            message: 'تم استرجاع بيانات المستوى بنجاح'
        );
    }

    public function update(UpdateLevelRequest $request, Level $level): JsonResponse
    {
        $level->update($request->validated());

        return ApiResponse::success(
            data: new LevelResource($level),
            message: 'تم تحديث بيانات المستوى بنجاح'
        );
    }

    public function destroy(Level $level): JsonResponse
    {
        if ($level->games()->exists()) {
            return ApiResponse::error(
                message: 'لا يمكن حذف هذا المستوى لأنه مرتبط ببعض الألعاب حالياً.',
                errorCode: 'LEVEL_IN_USE',
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $level->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف المستوى بنجاح'
        );
    }
}
