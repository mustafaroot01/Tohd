<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Activations\RedeemActivationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\RedeemActivationRequest;
use App\Http\Resources\Api\V1\ActivationCodeResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $assignment = $user->activeCurriculumAssignment()->with(['curriculum', 'activation.product'])->first();

        if (! $assignment) {
            return ApiResponse::success(
                data: null,
                message: 'لا يوجد تفعيل نشط حالياً لهذا الحساب'
            );
        }

        return ApiResponse::success(
            data: [
                'activation' => $assignment->activation ? new ActivationCodeResource($assignment->activation) : null,
                'starts_at' => $assignment->starts_at?->toISOString(),
                'ends_at' => $assignment->ends_at?->toISOString(),
                'days_remaining' => max(0, (int) now()->diffInDays($assignment->ends_at, false)),
            ],
            message: 'تم استرجاع بيانات التفعيل بنجاح'
        );
    }

    public function redeem(RedeemActivationRequest $request, RedeemActivationAction $action): JsonResponse
    {
        $assignment = $action->execute(
            codeString: $request->validated('code'),
            user: $request->user()
        );

        return ApiResponse::success(
            data: [
                'activation' => new ActivationCodeResource($assignment->activation),
                'starts_at' => $assignment->starts_at?->toISOString(),
                'ends_at' => $assignment->ends_at?->toISOString(),
                'curriculum' => [
                    'id' => $assignment->curriculum?->id,
                    'code' => $assignment->curriculum?->code,
                    'name' => $assignment->curriculum?->name,
                ],
            ],
            message: 'تم تفعيل كود الاشتراك والمنهج التدريبي بنجاح'
        );
    }
}
