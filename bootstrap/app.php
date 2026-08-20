<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsSubscriber;
use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('app:expire-subscriptions')->daily();
        $schedule->command('app:cleanup-expired-otps')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureIsAdmin::class,
            'subscriber' => EnsureIsSubscriber::class,
            'force.json' => ForceJsonResponse::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    message: 'بيانات الإدخال غير صالحة',
                    errorCode: 'VALIDATION_ERROR',
                    errors: $e->errors(),
                    status: Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    message: 'يجب تسجيل الدخول للوصول إلى هذا الرابط',
                    errorCode: 'UNAUTHENTICATED',
                    status: Response::HTTP_UNAUTHORIZED
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    message: 'العنصر أو المسار المطلوب غير موجود',
                    errorCode: 'RESOURCE_NOT_FOUND',
                    status: Response::HTTP_NOT_FOUND
                );
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    message: 'ليس لديك الصلاحية لتنفيذ هذا الإجراء',
                    errorCode: 'FORBIDDEN',
                    status: Response::HTTP_FORBIDDEN
                );
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    message: 'عدد المحاولات كبير جداً، يرجى المحاولة لاحقاً',
                    errorCode: 'TOO_MANY_REQUESTS',
                    status: Response::HTTP_TOO_MANY_REQUESTS
                );
            }
        });
    })->create();
