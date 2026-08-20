<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ActivationStatus;
use App\Enums\CurriculumStatus;
use App\Enums\GameSessionStatus;
use App\Enums\GameStatus;
use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Subscriber;
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
            'subscribers_total' => Subscriber::count(),
            'subscribers_active' => Subscriber::where('status', SubscriberStatus::ACTIVE)->count(),
            'subscribers_unverified' => Subscriber::where('status', SubscriberStatus::UNVERIFIED)->count(),
            'subscribers_suspended' => Subscriber::where('status', SubscriberStatus::SUSPENDED)->count(),
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
            'total_revenue' => (float) ActivationCode::where('activation_codes.status', ActivationStatus::ACTIVATED)
                ->join('products', 'activation_codes.product_id', '=', 'products.id')
                ->sum('products.price'),
            'today_activations_count' => ActivationCode::where('status', ActivationStatus::ACTIVATED)
                ->where('activated_at', '>=', $todayStart)
                ->count(),
            'today_revenue' => (float) ActivationCode::where('activation_codes.status', ActivationStatus::ACTIVATED)
                ->where('activation_codes.activated_at', '>=', $todayStart)
                ->join('products', 'activation_codes.product_id', '=', 'products.id')
                ->sum('products.price'),
            'month_activations_count' => ActivationCode::where('status', ActivationStatus::ACTIVATED)
                ->where('activated_at', '>=', now()->startOfMonth())
                ->count(),
            'month_revenue' => (float) ActivationCode::where('activation_codes.status', ActivationStatus::ACTIVATED)
                ->where('activation_codes.activated_at', '>=', now()->startOfMonth())
                ->join('products', 'activation_codes.product_id', '=', 'products.id')
                ->sum('products.price'),
        ];

        return ApiResponse::success(
            data: $stats,
            message: 'تم استرجاع إحصائيات لوحة التحكم بنجاح'
        );
    }
}
