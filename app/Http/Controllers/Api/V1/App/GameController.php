<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Exceptions\GameNotPublishedException;
use App\Exceptions\NoActiveCurriculumException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\App\AppGameResource;
use App\Models\Game;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function show(Game $game, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $game->isPlayable()) {
            throw new GameNotPublishedException('اللعبة غير متاحة للتشغيل');
        }

        $assignment = $user->activeCurriculumAssignment;
        if (! $assignment) {
            throw new NoActiveCurriculumException('يجب امتلاك اشتراك فعال للوصول إلى هذه اللعبة');
        }

        $game->load(['axis', 'skill', 'assets']);

        return ApiResponse::success(
            data: new AppGameResource($game),
            message: 'تم استرجاع بيانات اللعبة بنجاح'
        );
    }
}
