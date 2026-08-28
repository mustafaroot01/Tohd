<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\App\ProgressResource;
use App\Services\ProgressCalculationService;
use App\Services\WeeklyReportService;
use App\Support\ApiResponse;
use App\Support\WeekWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        return ApiResponse::success(
            data: new ProgressResource($progressService->getOverallProgress($request->user())),
            message: 'تم استرجاع ملخص التقدم الإجمالي بنجاح'
        );
    }

    public function daily(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        return ApiResponse::success(
            data: new ProgressResource($progressService->getPeriodicProgress($request->user(), 'daily')),
            message: 'تم استرجاع تقرير الأداء اليومي بنجاح'
        );
    }

    /**
     * The weekly evaluation, game by game. `?week=YYYY-MM-DD` picks the week
     * containing that date (Saturday to Friday); default is the current week.
     */
    public function weekly(Request $request, WeeklyReportService $weekly): JsonResponse
    {
        $validated = $request->validate([
            'week' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        return ApiResponse::success(
            data: new ProgressResource($weekly->build($request->user(), WeekWindow::containing($validated['week'] ?? null))),
            message: 'تم استرجاع التقرير الأسبوعي بنجاح'
        );
    }

    public function monthly(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        return ApiResponse::success(
            data: new ProgressResource($progressService->getPeriodicProgress($request->user(), 'monthly')),
            message: 'تم استرجاع تقرير الأداء الشهري بنجاح'
        );
    }
}
