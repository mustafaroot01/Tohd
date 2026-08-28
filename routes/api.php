<?php

use App\Http\Controllers\Api\V1\Admin\ActivationCodeController;
use App\Http\Controllers\Api\V1\Admin\AdminProfileController;
use App\Http\Controllers\Api\V1\Admin\AssetController;
use App\Http\Controllers\Api\V1\Admin\AxisController;
use App\Http\Controllers\Api\V1\Admin\CurriculumController as AdminCurriculumController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\GameAssetController;
use App\Http\Controllers\Api\V1\Admin\GameController as AdminGameController;
use App\Http\Controllers\Api\V1\Admin\GovernorateController;
use App\Http\Controllers\Api\V1\Admin\LevelController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\SkillController;
use App\Http\Controllers\Api\V1\Admin\SubscriberController;
use App\Http\Controllers\Api\V1\Admin\SystemSettingController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\App\ActivationController as AppActivationController;
use App\Http\Controllers\Api\V1\App\AttemptController;
use App\Http\Controllers\Api\V1\App\AuthController as AppAuthController;
use App\Http\Controllers\Api\V1\App\CurriculumController as AppCurriculumController;
use App\Http\Controllers\Api\V1\App\GameController as AppGameController;
use App\Http\Controllers\Api\V1\App\GovernorateController as AppGovernorateController;
use App\Http\Controllers\Api\V1\App\HomeController;
use App\Http\Controllers\Api\V1\App\ProfileController;
use App\Http\Controllers\Api\V1\App\ProgressController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (V1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('force.json')->group(function () {

    // ==========================================
    // 1. Authentication Endpoints (System Users / Admins)
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:15,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // ==========================================
    // 2. Admin Dashboard & Management Endpoints
    // ==========================================
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Profile
        Route::get('profile', [AdminProfileController::class, 'show']);
        Route::put('profile', [AdminProfileController::class, 'update']);

        Route::apiResource('axes', AxisController::class, ['parameters' => ['axes' => 'axis']]);
        Route::apiResource('skills', SkillController::class);
        Route::apiResource('levels', LevelController::class);
        Route::apiResource('governorates', GovernorateController::class);

        // Assets / Lottie Management
        Route::get('assets', [AssetController::class, 'index']);
        Route::post('assets', [AssetController::class, 'store']);
        Route::get('assets/{asset}', [AssetController::class, 'show']);
        Route::post('assets/{asset}', [AssetController::class, 'update']);
        Route::delete('assets/{asset}', [AssetController::class, 'destroy']);

        // Games Management & Publishing Lifecycle
        Route::apiResource('games', AdminGameController::class);
        Route::post('games/{game}/validate', [AdminGameController::class, 'validateGame']);
        Route::post('games/{game}/approve', [AdminGameController::class, 'approve']);
        Route::post('games/{game}/publish', [AdminGameController::class, 'publish']);
        Route::post('games/{game}/archive', [AdminGameController::class, 'archive']);

        // Game Assets (Lottie linking)
        Route::get('games/{game}/assets', [GameAssetController::class, 'index']);
        Route::post('games/{game}/assets', [GameAssetController::class, 'attach']);
        Route::put('games/{game}/assets/{gameAsset}', [GameAssetController::class, 'updateRole']);
        Route::delete('games/{game}/assets/{gameAsset}', [GameAssetController::class, 'detach']);

        // Curriculum Builder & Structure
        Route::apiResource('curriculums', AdminCurriculumController::class);
        Route::post('curriculums/{curriculum}/publish', [AdminCurriculumController::class, 'publish']);
        Route::post('curriculums/{curriculum}/months', [AdminCurriculumController::class, 'addMonth']);
        Route::post('curriculums/months/{month}/weeks', [AdminCurriculumController::class, 'addWeek']);
        Route::post('curriculums/weeks/{week}/days', [AdminCurriculumController::class, 'addDay']);
        Route::put('curriculums/days/{day}', [AdminCurriculumController::class, 'updateDay']);
        Route::delete('curriculums/days/{day}', [AdminCurriculumController::class, 'deleteDay']);
        Route::post('curriculums/days/{day}/games', [AdminCurriculumController::class, 'attachGame']);
        Route::delete('curriculums/days/{day}/games/{game}', [AdminCurriculumController::class, 'detachGame']);

        // Products Management
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/activate', [ProductController::class, 'activate']);
        Route::post('products/{product}/deactivate', [ProductController::class, 'deactivate']);

        // Activation Codes Management
        Route::get('activation-codes', [ActivationCodeController::class, 'index']);
        Route::post('activation-codes/generate', [ActivationCodeController::class, 'generate']);
        Route::get('activation-codes/{activation}', [ActivationCodeController::class, 'show']);
        Route::post('activation-codes/{activation}/revoke', [ActivationCodeController::class, 'revoke']);

        // System Users Overview
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);

        // Subscribers Management
        Route::get('subscribers', [SubscriberController::class, 'index']);
        Route::post('subscribers', [SubscriberController::class, 'store']);
        Route::get('subscribers/{subscriber}', [SubscriberController::class, 'show']);
        Route::get('subscribers/{subscriber}/weekly', [SubscriberController::class, 'weekly']);
        Route::put('subscribers/{subscriber}', [SubscriberController::class, 'update']);
        Route::post('subscribers/{subscriber}/suspend', [SubscriberController::class, 'suspend']);
        Route::post('subscribers/{subscriber}/reactivate', [SubscriberController::class, 'reactivate']);
        Route::post('subscribers/{subscriber}/assignments/{assignment}/cancel', [SubscriberController::class, 'cancelAssignment']);

        // System Settings Management
        Route::get('settings', [SystemSettingController::class, 'index']);
        Route::post('settings', [SystemSettingController::class, 'update']);
        Route::post('settings/test-sms', [SystemSettingController::class, 'testSms']);
    });

    // Public Config / Branding Endpoints
    Route::get('settings/public', [SystemSettingController::class, 'getPublicSettings']);

    // ==========================================
    // 3. Single-Account Mobile App Endpoints (Subscribers)
    // ==========================================
    Route::prefix('app')->middleware('maintenance')->group(function () {
        // Subscriber Authentication
        Route::prefix('auth')->group(function () {
            Route::post('register', [AppAuthController::class, 'register'])->middleware('throttle:otp-send');
            Route::post('otp/verify', [AppAuthController::class, 'verifyOtp'])->middleware('throttle:otp-verify');
            Route::post('otp/resend', [AppAuthController::class, 'resendOtp'])->middleware('throttle:otp-send');
            Route::post('login', [AppAuthController::class, 'login'])->middleware('throttle:15,1');
            Route::post('password/forgot', [AppAuthController::class, 'forgotPassword'])->middleware('throttle:otp-send-recovery');
            Route::post('password/reset', [AppAuthController::class, 'resetPassword'])->middleware('throttle:otp-verify');
            Route::post('logout', [AppAuthController::class, 'logout'])->middleware(['auth:sanctum', 'subscriber']);
        });
    });

    Route::prefix('app')->middleware(['maintenance', 'auth:sanctum', 'subscriber'])->group(function () {
        // App Home & Profile
        Route::get('home', [HomeController::class, 'index']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);

        // The second registration step. Both routes 404 while the feature is
        // switched off, so nothing about it can be discovered from the API.
        Route::middleware('profile.feature')->group(function () {
            Route::get('governorates', [AppGovernorateController::class, 'index']);
            Route::post('profile/details', [ProfileController::class, 'saveDetails']);
        });

        // Everything below waits for that step whenever it is switched on.
        Route::middleware('profile.complete')->group(function () {
            // Activation & Subscription
            Route::get('activation', [AppActivationController::class, 'show']);
            Route::post('activations/redeem', [AppActivationController::class, 'redeem'])->middleware('throttle:5,1,redeem');

            // Curriculum & Daily Training
            Route::get('curriculum', [AppCurriculumController::class, 'show']);
            Route::get('curriculum/today', [AppCurriculumController::class, 'today']);

            // Playable Games
            Route::get('games/{game}', [AppGameController::class, 'show']);

            // Game Sessions Telemetry
            // one attempt: start (no write) → complete (graded on the server's clock)
            Route::post('games/{game}/attempts', [AttemptController::class, 'start'])->middleware('throttle:60,1,attempts');
            Route::post('games/{game}/attempts/complete', [AttemptController::class, 'complete'])->middleware('throttle:60,1,attempts');
            Route::post('games/{game}/skip', [AttemptController::class, 'skip'])->middleware('throttle:30,1,skip');

            // Performance Progress (Training Telemetry)
            Route::get('progress', [ProgressController::class, 'index']);
            Route::get('progress/daily', [ProgressController::class, 'daily']);
            Route::get('progress/weekly', [ProgressController::class, 'weekly']);
            Route::get('progress/monthly', [ProgressController::class, 'monthly']);
        });
    });
});
