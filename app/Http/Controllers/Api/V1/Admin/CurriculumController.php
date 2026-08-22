<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Curriculum\AddDayAction;
use App\Actions\Curriculum\AddMonthAction;
use App\Actions\Curriculum\AddWeekAction;
use App\Actions\Curriculum\AttachGameToDayAction;
use App\Actions\Curriculum\CreateCurriculumAction;
use App\Actions\Curriculum\DetachGameFromDayAction;
use App\Actions\Curriculum\PublishCurriculumAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AttachGameToDayRequest;
use App\Http\Requests\Api\V1\Admin\StoreCurriculumDayRequest;
use App\Http\Requests\Api\V1\Admin\StoreCurriculumMonthRequest;
use App\Http\Requests\Api\V1\Admin\StoreCurriculumRequest;
use App\Http\Requests\Api\V1\Admin\StoreCurriculumWeekRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCurriculumRequest;
use App\Http\Resources\Api\V1\Admin\AdminCurriculumResource;
use App\Models\Curriculum;
use App\Models\CurriculumDay;
use App\Models\CurriculumMonth;
use App\Models\CurriculumWeek;
use App\Models\Game;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurriculumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Curriculum::withCount('months');

        TableQuery::filters($query, $request, ['status']);

        TableQuery::search($query, $request, ['name', 'code']);

        TableQuery::sort($query, $request, ['name', 'code', 'status', 'version', 'published_at', 'created_at'], 'created_at');

        $curriculums = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: AdminCurriculumResource::collection($curriculums),
            message: 'تم استرجاع قائمة المناهج بنجاح',
            meta: TableQuery::meta($curriculums)
        );
    }

    public function store(StoreCurriculumRequest $request, CreateCurriculumAction $action): JsonResponse
    {
        $curriculum = $action->execute($request->validated(), $request->user());

        return ApiResponse::success(
            data: new AdminCurriculumResource($curriculum),
            message: 'تم إنشاء المنهج بنجاح كمسودة',
            status: Response::HTTP_CREATED
        );
    }

    public function show(Curriculum $curriculum): JsonResponse
    {
        $curriculum->load(['months.weeks.days.dayGames.game', 'creator', 'updater']);

        return ApiResponse::success(
            data: new AdminCurriculumResource($curriculum),
            message: 'تم استرجاع تفاصيل المنهج بنجاح'
        );
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): JsonResponse
    {
        $curriculum->update($request->validated());

        return ApiResponse::success(
            data: new AdminCurriculumResource($curriculum->fresh()),
            message: 'تم تحديث بيانات المنهج بنجاح'
        );
    }

    public function destroy(Curriculum $curriculum): JsonResponse
    {
        $curriculum->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف المنهج بنجاح'
        );
    }

    public function publish(Curriculum $curriculum, PublishCurriculumAction $action, Request $request): JsonResponse
    {
        $published = $action->execute($curriculum, $request->user());

        return ApiResponse::success(
            data: new AdminCurriculumResource($published->load(['months.weeks.days.dayGames.game'])),
            message: 'تم نشر المنهج التدريبي بنجاح'
        );
    }

    public function addMonth(StoreCurriculumMonthRequest $request, Curriculum $curriculum, AddMonthAction $action): JsonResponse
    {
        $month = $action->execute($curriculum, $request->validated());

        return ApiResponse::success(
            data: $month,
            message: 'تمت إضافة الشهر إلى المنهج بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function addWeek(StoreCurriculumWeekRequest $request, CurriculumMonth $month, AddWeekAction $action): JsonResponse
    {
        $week = $action->execute($month, $request->validated());

        return ApiResponse::success(
            data: $week,
            message: 'تمت إضافة الأسبوع إلى الشهر بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function addDay(StoreCurriculumDayRequest $request, CurriculumWeek $week, AddDayAction $action): JsonResponse
    {
        if ($week->days()->count() >= 7) {
            return ApiResponse::error(
                message: 'لا يمكن إضافة أكثر من 7 أيام في الأسبوع الواحد.',
                errorCode: 'WEEK_DAYS_LIMIT_REACHED',
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $day = $action->execute($week, $request->validated());

        return ApiResponse::success(
            data: $day,
            message: 'تمت إضافة اليوم إلى الأسبوع بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function updateDay(Request $request, CurriculumDay $day): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'estimated_duration_seconds' => ['required', 'integer', 'min:60'],
            'sort_order' => ['sometimes', 'integer', 'min:1'],
        ]);

        $day->update($validated);

        return ApiResponse::success(
            data: $day,
            message: 'تم تحديث بيانات اليوم بنجاح'
        );
    }

    public function deleteDay(CurriculumDay $day): JsonResponse
    {
        $day->delete();

        return ApiResponse::success(
            data: null,
            message: 'تم حذف اليوم بنجاح'
        );
    }

    public function attachGame(AttachGameToDayRequest $request, CurriculumDay $day, AttachGameToDayAction $action): JsonResponse
    {
        $game = Game::findOrFail($request->validated('game_id'));
        $dayGame = $action->execute($day, $game, $request->validated());

        return ApiResponse::success(
            data: $dayGame->load('game'),
            message: 'تم ربط اللعبة باليوم التدريبي بنجاح',
            status: Response::HTTP_CREATED
        );
    }

    public function detachGame(CurriculumDay $day, Game $game, DetachGameFromDayAction $action): JsonResponse
    {
        $action->execute($day, $game);

        return ApiResponse::success(
            data: null,
            message: 'تم فك ارتباط اللعبة باليوم التدريبي بنجاح'
        );
    }
}
