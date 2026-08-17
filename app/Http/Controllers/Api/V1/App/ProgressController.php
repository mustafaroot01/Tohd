<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\App\ProgressResource;
use App\Services\ProgressCalculationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        $progress = $progressService->getOverallProgress($request->user());

        return ApiResponse::success(
            data: new ProgressResource($progress),
            message: 'تم استرجاع ملخص التقدم الإجمالي بنجاح'
        );
    }

    public function daily(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        $progress = $progressService->getPeriodicProgress($request->user(), 'daily');

        return ApiResponse::success(
            data: new ProgressResource($progress),
            message: 'تم استرجاع تقرير الأداء اليومي بنجاح'
        );
    }

    public function weekly(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        $progress = $progressService->getPeriodicProgress($request->user(), 'weekly');

        return ApiResponse::success(
            data: new ProgressResource($progress),
            message: 'تم استرجاع تقرير الأداء الأسبوعي بنجاح'
        );
    }

    public function monthly(Request $request, ProgressCalculationService $progressService): JsonResponse
    {
        $progress = $progressService->getPeriodicProgress($request->user(), 'monthly');

        return ApiResponse::success(
            data: new ProgressResource($progress),
            message: 'تم استرجاع تقرير الأداء الشهري بنجاح'
        );
    }
}
