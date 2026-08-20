<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Enums\GameSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\App\HomeResource;
use App\Models\GameSession;
use App\Services\DailyCurriculumService;
use App\Services\ProgressCalculationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, DailyCurriculumService $dailyService, ProgressCalculationService $progressService): JsonResponse
    {
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

        $progressSummary = $progressService->getOverallProgress($user);

        $continueSession = GameSession::where('subscriber_id', $user->id)
            ->where('status', GameSessionStatus::STARTED)
            ->with(['game.axis', 'game.skill', 'curriculumDay'])
            ->latest('started_at')
            ->first();

        $homePayload = [
            'user' => $user,
            'activation' => $assignment?->activation,
            'assignment' => $assignment,
            'today' => $todayData,
            'progress' => $progressSummary,
            'continue_session' => $continueSession,
        ];

        return ApiResponse::success(
            data: new HomeResource($homePayload),
            message: 'تم استرجاع بيانات الصفحة الرئيسية بنجاح'
        );
    }
}
