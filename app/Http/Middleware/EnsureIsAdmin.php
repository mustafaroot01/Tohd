<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
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

        if (! $user instanceof User) {
            return ApiResponse::error(
                message: 'ليس لديك الصلاحية الكافية للوصول إلى هذا الإجراء',
                errorCode: 'FORBIDDEN',
                status: Response::HTTP_FORBIDDEN
            );
        }

        if (! $user->isActive()) {
            return ApiResponse::error(
                message: 'تم تجميد أو إيقاف هذا الحساب، يرجى التواصل مع الإدارة',
                errorCode: 'ACCOUNT_SUSPENDED',
                status: Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
