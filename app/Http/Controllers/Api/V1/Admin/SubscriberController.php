<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Assignments\CancelAssignmentAction;
use App\Actions\Subscribers\CreateSubscriberAction;
use App\Actions\Subscribers\ReactivateSubscriberAction;
use App\Actions\Subscribers\SuspendSubscriberAction;
use App\Actions\Subscribers\UpdateSubscriberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\CancelAssignmentRequest;
use App\Http\Requests\Api\V1\Admin\StoreSubscriberRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSubscriberRequest;
use App\Http\Resources\Api\V1\Admin\SubscriberDetailResource;
use App\Http\Resources\Api\V1\SubscriberResource;
use App\Models\Subscriber;
use App\Models\UserCurriculumAssignment;
use App\Services\ProgressCalculationService;
use App\Services\WeeklyReportService;
use App\Support\ApiResponse;
use App\Support\TableQuery;
use App\Support\WeekWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscriber::query();

        TableQuery::filters($query, $request, ['status']);

        TableQuery::search($query, $request, ['name', 'phone']);

        TableQuery::sort($query, $request, ['name', 'phone', 'status', 'last_activity_at', 'created_at'], 'created_at');

        $subscribers = $query->paginate(TableQuery::perPage($request));

        return ApiResponse::success(
            data: SubscriberResource::collection($subscribers),
            message: 'تم استرجاع قائمة المشتركين بنجاح',
            meta: TableQuery::meta($subscribers)
        );
    }

    public function show(Subscriber $subscriber, ProgressCalculationService $progressService, WeeklyReportService $weekly): JsonResponse
    {
        $subscriber->load([
            'activeCurriculumAssignment.curriculum',
            'activeCurriculumAssignment.activation.product',
            'curriculumAssignments' => fn ($q) => $q->with(['curriculum', 'activation.product'])->orderByDesc('starts_at'),
            'activations' => fn ($q) => $q->with('product.curriculum')->orderByDesc('activated_at'),
            'activities' => fn ($q) => $q->limit(100),
            'profile.governorate',
        ]);

        return ApiResponse::success(
            data: new SubscriberDetailResource([
                'subscriber' => $subscriber,
                'progress' => $progressService->getOverallProgress($subscriber),
                'weekly' => $weekly->build($subscriber, WeekWindow::containing()),
            ]),
            message: 'تم استرجاع بيانات المشترك بنجاح'
        );
    }

    /** The weekly evaluation for any week: `?week=YYYY-MM-DD` inside the week wanted. */
    public function weekly(Request $request, Subscriber $subscriber, WeeklyReportService $weekly): JsonResponse
    {
        $validated = $request->validate([
            'week' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        return ApiResponse::success(
            data: $weekly->build($subscriber, WeekWindow::containing($validated['week'] ?? null)),
            message: 'تم استرجاع التقرير الأسبوعي بنجاح'
        );
    }

    public function store(StoreSubscriberRequest $request, CreateSubscriberAction $action): JsonResponse
    {
        $subscriber = $action->execute($request->validated(), $request->user());

        return ApiResponse::success(
            data: new SubscriberResource($subscriber),
            message: 'تم إنشاء حساب المشترك بنجاح',
            status: 201
        );
    }

    public function update(UpdateSubscriberRequest $request, Subscriber $subscriber, UpdateSubscriberAction $action): JsonResponse
    {
        $updated = $action->execute($subscriber, $request->validated(), $request->user());

        return ApiResponse::success(
            data: new SubscriberResource($updated),
            message: 'تم تحديث بيانات المشترك بنجاح'
        );
    }

    public function suspend(Subscriber $subscriber, SuspendSubscriberAction $action, Request $request): JsonResponse
    {
        $suspended = $action->execute($subscriber, $request->user());

        return ApiResponse::success(
            data: new SubscriberResource($suspended),
            message: 'تم إيقاف حساب المشترك'
        );
    }

    public function reactivate(Subscriber $subscriber, ReactivateSubscriberAction $action, Request $request): JsonResponse
    {
        $reactivated = $action->execute($subscriber, $request->user());

        return ApiResponse::success(
            data: new SubscriberResource($reactivated),
            message: 'تم إعادة تفعيل حساب المشترك'
        );
    }

    public function cancelAssignment(CancelAssignmentRequest $request, Subscriber $subscriber, UserCurriculumAssignment $assignment, CancelAssignmentAction $action): JsonResponse
    {
        abort_if($assignment->subscriber_id !== $subscriber->id, 404);

        $cancelled = $action->execute($assignment, $request->validated('reason'));

        return ApiResponse::success(
            data: $cancelled,
            message: 'تم إلغاء الاشتراك بنجاح'
        );
    }
}
