<?php

use App\Http\Controllers\Api\V1\Admin\ActivationCodeController;
use App\Http\Controllers\Api\V1\Admin\AssetController;
use App\Http\Controllers\Api\V1\Admin\AxisController;
use App\Http\Controllers\Api\V1\Admin\CurriculumController as AdminCurriculumController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\GameController as AdminGameController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\SkillController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\App\ActivationController as AppActivationController;
use App\Http\Controllers\Api\V1\App\CurriculumController as AppCurriculumController;
use App\Http\Controllers\Api\V1\App\GameController as AppGameController;
use App\Http\Controllers\Api\V1\App\GameSessionController;
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
    // 1. Authentication Endpoints
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:15,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:15,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // ==========================================
    // 2. Admin Dashboard & Management Endpoints
    // ==========================================
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('axes', AxisController::class, ['parameters' => ['axes' => 'axis']]);
        Route::apiResource('skills', SkillController::class);

        // Assets / Lottie Management
        Route::get('assets', [AssetController::class, 'index']);
        Route::post('assets', [AssetController::class, 'store']);
        Route::get('assets/{asset}', [AssetController::class, 'show']);
        Route::delete('assets/{asset}', [AssetController::class, 'destroy']);

        // Games Management & Publishing Lifecycle
        Route::apiResource('games', AdminGameController::class);
        Route::post('games/{game}/validate', [AdminGameController::class, 'validateGame']);
        Route::post('games/{game}/approve', [AdminGameController::class, 'approve']);
        Route::post('games/{game}/publish', [AdminGameController::class, 'publish']);
        Route::post('games/{game}/archive', [AdminGameController::class, 'archive']);

        // Curriculum Builder & Structure
        Route::apiResource('curriculums', AdminCurriculumController::class);
        Route::post('curriculums/{curriculum}/publish', [AdminCurriculumController::class, 'publish']);
        Route::post('curriculums/{curriculum}/months', [AdminCurriculumController::class, 'addMonth']);
        Route::post('curriculums/months/{month}/weeks', [AdminCurriculumController::class, 'addWeek']);
        Route::post('curriculums/weeks/{week}/days', [AdminCurriculumController::class, 'addDay']);
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

        // Users Overview
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
    });

    // ==========================================
    // 3. Single-Account Mobile App Endpoints
    // ==========================================
    Route::prefix('app')->middleware(['auth:sanctum', 'role:USER'])->group(function () {
        // App Home & Profile
        Route::get('home', [HomeController::class, 'index']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);

        // Activation & Subscription
        Route::get('activation', [AppActivationController::class, 'show']);
        Route::post('activations/redeem', [AppActivationController::class, 'redeem'])->middleware('throttle:5,1');

        // Curriculum & Daily Training
        Route::get('curriculum', [AppCurriculumController::class, 'show']);
        Route::get('curriculum/today', [AppCurriculumController::class, 'today']);

        // Playable Games
        Route::get('games/{game}', [AppGameController::class, 'show']);

        // Game Sessions Telemetry
        Route::post('games/{game}/sessions', [GameSessionController::class, 'start'])->middleware('throttle:60,1');
        Route::post('sessions/{session}/complete', [GameSessionController::class, 'complete'])->middleware('throttle:60,1');
        Route::post('sessions/{session}/abandon', [GameSessionController::class, 'abandon']);

        // Performance Progress (Training Telemetry)
        Route::get('progress', [ProgressController::class, 'index']);
        Route::get('progress/daily', [ProgressController::class, 'daily']);
        Route::get('progress/weekly', [ProgressController::class, 'weekly']);
        Route::get('progress/monthly', [ProgressController::class, 'monthly']);
    });
});
