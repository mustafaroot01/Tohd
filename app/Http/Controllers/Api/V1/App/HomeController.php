<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\App\HomeResource;
use App\Services\DailyCurriculumService;
use App\Services\ProfileCompletionService;
use App\Services\ProgressCalculationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * The app's first call after launch. It carries the completion status when
     * that feature is on, so the app knows on the opening screen whether to ask
     * for the remaining details — and carries no trace of it when it is off.
     */
    public function index(
        Request $request,
        DailyCurriculumService $dailyService,
        ProgressCalculationService $progressService,
        ProfileCompletionService $completion,
    ): JsonResponse {
        $user = $request->user();
        $assignment = $user->activeCurriculumAssignment;

        $todayData = null;
        if ($assignment) {
            try {
                $todayData = $dailyService->getTodayPlan($user);
            } catch (\Throwable) {
                $todayData = null;
            }
        }

        return ApiResponse::success(
            data: new HomeResource([
                'user' => $user,
                'activation' => $assignment?->activation,
                'assignment' => $assignment,
                'today' => $todayData,
                'progress' => $progressService->getOverallProgress($user),
                'profile_completion' => $completion->status($user),
            ]),
            message: 'تم استرجاع بيانات الصفحة الرئيسية بنجاح'
        );
    }
}
