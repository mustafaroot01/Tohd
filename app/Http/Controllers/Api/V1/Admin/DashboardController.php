<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ActivationStatus;
use App\Enums\CurriculumStatus;
use App\Enums\GameSessionStatus;
use App\Enums\GameStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get system statistics and KPI summary for Admin dashboard.
     */
    public function index(): JsonResponse
    {
        $todayStart = now()->startOfDay();

        $stats = [
            'users_count' => User::where('role', 'USER')->count(),
            'active_users_count' => User::where('role', 'USER')->where('status', UserStatus::ACTIVE)->count(),
            'active_activations_count' => ActivationCode::where('status', ActivationStatus::ACTIVATED)->count(),
            'available_activations_count' => ActivationCode::where('status', ActivationStatus::AVAILABLE)->count(),
            'expired_activations_count' => ActivationCode::where('status', ActivationStatus::EXPIRED)->count(),
            'games_count' => Game::count(),
            'published_games_count' => Game::where('status', GameStatus::PUBLISHED)->count(),
            'curriculums_count' => Curriculum::count(),
            'published_curriculums_count' => Curriculum::where('status', CurriculumStatus::PUBLISHED)->count(),
            'today_sessions_count' => GameSession::where('started_at', '>=', $todayStart)->count(),
            'today_completed_sessions_count' => GameSession::where('completed_at', '>=', $todayStart)->where('status', GameSessionStatus::COMPLETED)->count(),
            'average_accuracy' => (float) round(GameSession::where('status', GameSessionStatus::COMPLETED)->avg('accuracy') ?? 0, 1),
        ];

        return ApiResponse::success(
            data: $stats,
            message: 'تم استرجاع إحصائيات لوحة التحكم بنجاح'
        );
    }
}
