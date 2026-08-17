<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSkillRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSkillRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Models\Skill;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Skill::with('axis')->withCount('games')->orderBy('sort_order');

        if ($request->filled('axis_id')) {
            $query->where('axis_id', $request->input('axis_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
        }

        $skills = $query->paginate($perPage);

        return ApiResponse::success(
            data: SkillResource::collection($skills),
            message: 'تم استرجاع قائمة المهارات بنجاح',
            meta: [
                'current_page' => $skills->currentPage(),
                'per_page' => $skills->perPage(),
                'total' => $skills->total(),
            ]
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
