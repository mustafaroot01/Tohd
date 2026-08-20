<?php

namespace App\Http\Middleware;

use App\Models\Subscriber;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsSubscriber
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(
                message: 'يجب تسجيل الدخول للوصول إلى هذا الرابط',
                errorCode: 'UNAUTHENTICATED',
                status: Response::HTTP_UNAUTHORIZED
            );
        }

        if (! $user instanceof Subscriber) {
            return ApiResponse::error(
                message: 'ليس لديك الصلاحية الكافية للوصول إلى هذا الإجراء',
                errorCode: 'FORBIDDEN',
                status: Response::HTTP_FORBIDDEN
            );
        }

        if ($user->isSuspended()) {
            return ApiResponse::error(
                message: 'تم إيقاف هذا الحساب، يرجى التواصل مع الإدارة',
                errorCode: 'ACCOUNT_SUSPENDED',
                status: Response::HTTP_FORBIDDEN
            );
        }

        if ($user->isUnverified()) {
            return ApiResponse::error(
                message: 'لم يتم التحقق من رقم الهاتف بعد، يرجى إكمال عملية التحقق',
                errorCode: 'ACCOUNT_UNVERIFIED',
                status: Response::HTTP_FORBIDDEN
            );
        }

        if (! $user->last_activity_at || $user->last_activity_at->lt(now()->subMinute())) {
            $user->forceFill(['last_activity_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
