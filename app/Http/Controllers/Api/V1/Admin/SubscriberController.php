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
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Subscriber::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->paginate($perPage);

        return ApiResponse::success(
            data: SubscriberResource::collection($subscribers),
            message: 'تم استرجاع قائمة المشتركين بنجاح',
            meta: [
                'current_page' => $subscribers->currentPage(),
                'per_page' => $subscribers->perPage(),
                'total' => $subscribers->total(),
            ]
        );
    }

    public function show(Subscriber $subscriber, ProgressCalculationService $progressService): JsonResponse
    {
        $subscriber->load([
            'activeCurriculumAssignment.curriculum',
            'activeCurriculumAssignment.activation.product',
            'curriculumAssignments' => fn ($q) => $q->with(['curriculum', 'activation.product'])->orderByDesc('starts_at'),
            'activations' => fn ($q) => $q->with('product.curriculum')->orderByDesc('activated_at'),
            'gameSessions' => fn ($q) => $q->with(['game'])->latest('started_at'),
            'activities' => fn ($q) => $q->limit(100),
        ]);

        return ApiResponse::success(
            data: new SubscriberDetailResource([
                'subscriber' => $subscriber,
                'progress' => $progressService->getOverallProgress($subscriber),
            ]),
            message: 'تم استرجاع بيانات المشترك بنجاح'
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
