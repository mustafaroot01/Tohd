<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LevelResource;
use App\Models\Level;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LevelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Level::withCount('games')->orderBy('level_number');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Allow fetching all without pagination if requested
        if ($request->boolean('all')) {
            return ApiResponse::success(
                data: LevelResource::collection($query->get()),
                message: 'تم استرجاع قائمة المستويات بنجاح'
            );
        }

        $levels = $query->paginate($perPage);

        return ApiResponse::success(
            data: LevelResource::collection($levels),
            message: 'تم استرجاع قائمة المستويات بنجاح',
            meta: [
                'current_page' => $levels->currentPage(),
                'per_page' => $levels->perPage(),
                'total' => $levels->total(),
            ]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level_number' => ['required', 'integer', 'unique:levels,level_number'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_age' => ['required', 'integer', 'min:1'],
            'max_age' => ['required', 'integer', 'min:1', 'gte:min_age'],
        ]);

        $level = Level::create($validated);

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

    public function update(Request $request, Level $level): JsonResponse
    {
        $validated = $request->validate([
            'level_number' => ['required', 'integer', 'unique:levels,level_number,' . $level->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_age' => ['required', 'integer', 'min:1'],
            'max_age' => ['required', 'integer', 'min:1', 'gte:min_age'],
        ]);

        $level->update($validated);

        return ApiResponse::success(
            data: new LevelResource($level),
            message: 'تم تحديث بيانات المستوى بنجاح'
        );
    }

    public function destroy(Level $level): JsonResponse
    {
        if ($level->games()->exists()) {
            return ApiResponse::error('لا يمكن حذف هذا المستوى لأنه مرتبط ببعض الألعاب حالياً.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $level->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف المستوى بنجاح'
        );
    }
}
