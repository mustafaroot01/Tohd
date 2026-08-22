<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Games\ApproveGameAction;
use App\Actions\Games\ArchiveGameAction;
use App\Actions\Games\CreateGameAction;
use App\Actions\Games\PublishGameAction;
use App\Actions\Games\UpdateGameAction;
use App\Actions\Games\ValidateGameAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreGameRequest;
use App\Http\Requests\Api\V1\Admin\UpdateGameRequest;
use App\Http\Resources\Api\V1\Admin\AdminGameResource;
use App\Models\Game;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Game::with(['axis', 'skill', 'gameLevel', 'assets']);

        TableQuery::filters($query, $request, ['status', 'type', 'axis_id', 'skill_id', 'difficulty']);

        TableQuery::search($query, $request, ['name', 'code']);

        TableQuery::sort($query, $request, ['name', 'code', 'status', 'type', 'difficulty', 'duration_seconds', 'published_at', 'created_at'], 'created_at');

        $games = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: AdminGameResource::collection($games),
            message: 'تم استرجاع قائمة الألعاب بنجاح',
            meta: TableQuery::meta($games)
        );
    }

    public function store(StoreGameRequest $request, CreateGameAction $action): JsonResponse
    {
        $game = $action->execute($request->validated(), $request->user());

        return ApiResponse::success(
            data: new AdminGameResource($game),
            message: 'تم إنشاء اللعبة بنجاح كمسودة',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Game $game): JsonResponse
    {
        $game->load(['axis', 'skill', 'gameLevel', 'assets', 'creator', 'updater']);

        return ApiResponse::success(
            data: new AdminGameResource($game),
            message: 'تم استرجاع تفاصيل اللعبة بنجاح'
        );
    }

    public function update(UpdateGameRequest $request, Game $game, UpdateGameAction $action): JsonResponse
    {
        $updated = $action->execute($game, $request->validated(), $request->user());

        return ApiResponse::success(
            data: new AdminGameResource($updated),
            message: 'تم تحديث بيانات اللعبة بنجاح'
        );
    }

    public function destroy(Game $game): JsonResponse
    {
        $game->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف اللعبة بنجاح'
        );
    }

    public function validateGame(Game $game, ValidateGameAction $action): JsonResponse
    {
        $action->execute($game);

        return ApiResponse::success(
            data: ['is_valid' => true],
            message: 'إعدادات وبنية اللعبة صالحة ومطابقة للمعايير'
        );
    }

    public function approve(Game $game, ApproveGameAction $action, Request $request): JsonResponse
    {
        $approved = $action->execute($game, $request->user());

        return ApiResponse::success(
            data: new AdminGameResource($approved->fresh(['axis', 'skill', 'assets'])),
            message: 'تم اعتماد اللعبة بنجاح'
        );
    }

    public function publish(Game $game, PublishGameAction $action, Request $request): JsonResponse
    {
        $published = $action->execute($game, $request->user());

        return ApiResponse::success(
            data: new AdminGameResource($published->fresh(['axis', 'skill', 'assets'])),
            message: 'تم نشر اللعبة بنجاح وأصبحت متاحة للتطبيق'
        );
    }

    public function archive(Game $game, ArchiveGameAction $action, Request $request): JsonResponse
    {
        $archived = $action->execute($game, $request->user());

        return ApiResponse::success(
            data: new AdminGameResource($archived->fresh(['axis', 'skill', 'assets'])),
            message: 'تمت أرشفة اللعبة بنجاح'
        );
    }
}
