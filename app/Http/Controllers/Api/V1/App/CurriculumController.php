<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Exceptions\NoActiveCurriculumException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\App\AppCurriculumResource;
use App\Services\DailyCurriculumService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $assignment = $request->user()->activeCurriculumAssignment;

        if (! $assignment) {
            throw new NoActiveCurriculumException('لا يوجد منهج تدريبي مفعل لهذا الحساب');
        }

        $curriculum = $assignment->curriculum()->with(['months.weeks.days.dayGames.game'])->firstOrFail();

        return ApiResponse::success(
            data: new AppCurriculumResource($curriculum),
            message: 'تم استرجاع المنهج التدريبي بنجاح'
        );
    }

    public function today(Request $request, DailyCurriculumService $dailyService): JsonResponse
    {
        $todayPlan = $dailyService->getTodayPlan($request->user());

        return ApiResponse::success(
            data: $todayPlan,
            message: 'تم استرجاع خطة التدريب اليومية بنجاح'
        );
    }
}
