<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Attempts\CompleteAttemptAction;
use App\Actions\Attempts\IssueAttemptAction;
use App\Actions\Attempts\SkipGameAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\CompleteAttemptRequest;
use App\Http\Requests\Api\V1\App\StartAttemptRequest;
use App\Models\Game;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * One attempt at a game: start → (the child watches) → complete.
 *
 * Start writes nothing and hands back a sealed token; complete grades the
 * attempt against that token on the server's clock. There is no session to
 * resume or abandon — an unfinished attempt simply never comes back.
 */
class AttemptController extends Controller
{
    public function start(StartAttemptRequest $request, Game $game, IssueAttemptAction $action): JsonResponse
    {
        $data = $action->execute($request->user(), $game, $request->validated('curriculum_day_id'));

        return ApiResponse::success(
            data: $data,
            message: 'بدأت المحاولة',
            status: Response::HTTP_CREATED
        );
    }

    public function complete(CompleteAttemptRequest $request, Game $game, CompleteAttemptAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $game, $request->validated());

        return ApiResponse::success(
            data: $result->toArray(),
            message: $result->replayed
                ? 'هذه المحاولة احتُسبت مسبقاً، وهذه نتيجتها'
                : ($result->passed ? 'أحسنت! اجتزت اللعبة' : 'احتُسبت المحاولة'),
        );
    }

    /**
     * The child chooses to move past a game they could not pass.
     *
     * Only allowed once the configured number of counted failures is reached;
     * the choice is recorded so a specialist can tell "skipped and kept going"
     * from "gave up and closed the app".
     */
    public function skip(StartAttemptRequest $request, Game $game, SkipGameAction $action): JsonResponse
    {
        $data = $action->execute($request->user(), $game, $request->validated('curriculum_day_id'));

        return ApiResponse::success(
            data: $data,
            message: 'تم تخطّي اللعبة، يمكنك المتابعة إلى اللعبة التالية'
        );
    }
}
