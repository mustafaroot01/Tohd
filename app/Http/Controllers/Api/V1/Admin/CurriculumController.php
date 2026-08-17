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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurriculumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Curriculum::withCount('months')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $curriculums = $query->paginate($perPage);

        return ApiResponse::success(
            data: AdminCurriculumResource::collection($curriculums),
            message: 'تم استرجاع قائمة المناهج بنجاح',
            meta: [
                'current_page' => $curriculums->currentPage(),
                'per_page' => $curriculums->perPage(),
                'total' => $curriculums->total(),
            ]
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
        $day = $action->execute($week, $request->validated());

        return ApiResponse::success(
            data: $day,
            message: 'تمت إضافة اليوم إلى الأسبوع بنجاح',
            status: Response::HTTP_CREATED
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
