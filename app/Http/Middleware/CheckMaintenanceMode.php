<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = SystemSetting::get();

        // Check if maintenance mode is enabled
        if ($settings->is_maintenance) {
            // Apply restrictions only to Client/App API requests
            // Admin management routes (v1/admin) and Auth routes (v1/auth) must remain accessible
            if ($request->is('api/v1/app/*') || $request->is('api/v1/app')) {
                return ApiResponse::error(
                    message: 'النظام حالياً في وضع الصيانة والتحديث المؤقت، يرجى المحاولة لاحقاً. شكراً لتفهمكم.',
                    errorCode: 'MAINTENANCE_MODE',
                    status: Response::HTTP_SERVICE_UNAVAILABLE
                );
            }
        }

        return $next($request);
    }
}
