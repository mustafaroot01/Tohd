<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ActivationStatus;
use App\Enums\CurriculumStatus;
use App\Enums\GameStatus;
use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Game;
use App\Models\Subscriber;
use App\Models\SubscriberGameDaily;
use App\Models\SubscriberGameProgress;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * System statistics and KPI summary for the admin dashboard.
     *
     * Play figures come from the daily table (today) and the board (all-time)
     * — bounded by subscribers × games, never by how much they played.
     */
    public function index(): JsonResponse
    {
        $todayStart = now()->startOfDay();
        $today = now()->toDateString();

        $todayPlay = SubscriberGameDaily::query()
            ->where('date', $today)
            ->selectRaw('COALESCE(SUM(attempts), 0) AS attempts, COUNT(passed_at) AS passed, COUNT(DISTINCT CASE WHEN attempts > 0 THEN subscriber_id END) AS active_subscribers')
            ->first();

        $board = SubscriberGameProgress::query()
            ->selectRaw('COALESCE(SUM(total_score_sum), 0) AS score_sum, COALESCE(SUM(total_attempts), 0) AS attempts')
            ->first();

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
            'today_attempts_count' => (int) $todayPlay->attempts,
            'today_passed_count' => (int) $todayPlay->passed,
            'today_active_subscribers' => (int) $todayPlay->active_subscribers,
            'average_score' => (int) $board->attempts > 0 ? round($board->score_sum / $board->attempts, 1) : null,
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
