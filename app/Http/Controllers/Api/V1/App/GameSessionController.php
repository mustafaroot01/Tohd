<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Sessions\AbandonGameSessionAction;
use App\Actions\Sessions\CompleteGameSessionAction;
use App\Actions\Sessions\StartGameSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\CompleteGameSessionRequest;
use App\Http\Requests\Api\V1\App\StartGameSessionRequest;
use App\Http\Resources\Api\V1\GameSessionResource;
use App\Models\CurriculumDay;
use App\Models\Game;
use App\Models\GameSession;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GameSessionController extends Controller
{
    public function start(StartGameSessionRequest $request, Game $game, StartGameSessionAction $action): JsonResponse
    {
        $curriculumDay = $request->filled('curriculum_day_id')
            ? CurriculumDay::find($request->validated('curriculum_day_id'))
            : null;

        $session = $action->execute(
            user: $request->user(),
            game: $game,
            curriculumDay: $curriculumDay,
            metadata: $request->validated('metadata')
        );

        return ApiResponse::success(
            data: new GameSessionResource($session),
            message: 'تم بدء جلسة اللعبة بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function complete(CompleteGameSessionRequest $request, GameSession $session, CompleteGameSessionAction $action): JsonResponse
    {
        $completedSession = $action->execute(
            session: $session,
            telemetry: $request->validated(),
            user: $request->user()
        );

        return ApiResponse::success(
            data: new GameSessionResource($completedSession),
            message: 'تم إنهاء الجلسة واحتساب النتيجة بنجاح'
        );
    }

    public function abandon(GameSession $session, AbandonGameSessionAction $action, \Illuminate\Http\Request $request): JsonResponse
    {
        $abandonedSession = $action->execute($session, $request->user());

        return ApiResponse::success(
            data: new GameSessionResource($abandonedSession),
            message: 'تم التراجع عن الجلسة'
        );
    }
}
