<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSkillRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSkillRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Models\Skill;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Skill::with('axis')->withCount('games');

        TableQuery::filters($query, $request, ['axis_id', 'status']);

        TableQuery::search($query, $request, ['name', 'slug']);

        TableQuery::sort($query, $request, ['name', 'slug', 'status', 'sort_order', 'created_at'], 'sort_order', 'asc');

        $skills = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: SkillResource::collection($skills),
            message: 'تم استرجاع قائمة المهارات بنجاح',
            meta: TableQuery::meta($skills)
        );
    }

    public function store(StoreSkillRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $skill = Skill::create($data);

        return ApiResponse::success(
            data: new SkillResource($skill->load('axis')),
            message: 'تم إنشاء المهارة بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Skill $skill): JsonResponse
    {
        $skill->load('axis');

        return ApiResponse::success(
            data: new SkillResource($skill),
            message: 'تم استرجاع بيانات المهارة بنجاح'
        );
    }

    public function update(UpdateSkillRequest $request, Skill $skill): JsonResponse
    {
        $skill->update($request->validated());

        return ApiResponse::success(
            data: new SkillResource($skill->fresh('axis')),
            message: 'تم تحديث بيانات المهارة بنجاح'
        );
    }

    public function destroy(Skill $skill): JsonResponse
    {
        $skill->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف المهارة بنجاح'
        );
    }
}
