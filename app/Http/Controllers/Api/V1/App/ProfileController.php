<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\SaveProfileDetailsRequest;
use App\Http\Requests\Api\V1\App\UpdateProfileRequest;
use App\Http\Resources\Api\V1\SubscriberProfileResource;
use App\Http\Resources\Api\V1\SubscriberResource;
use App\Models\SubscriberProfile;
use App\Services\ProfileCompletionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileCompletionService $completion) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: $this->payload($request->user()),
            message: 'تم استرجاع الملف الشخصي بنجاح'
        );
    }

    /**
     * Name, address and password. The phone is not editable here — see
     * UpdateProfileRequest.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $currentPassword = $data['current_password'] ?? null;
        unset($data['current_password']);

        if (isset($data['password'])) {
            if (! $currentPassword || ! Hash::check($currentPassword, $user->password)) {
                return ApiResponse::error(
                    message: 'كلمة المرور الحالية غير صحيحة',
                    errorCode: 'INVALID_CURRENT_PASSWORD',
                    status: Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return ApiResponse::success(
            data: $this->payload($user->fresh()),
            message: 'تم تحديث الملف الشخصي بنجاح'
        );
    }

    /**
     * The second registration step. Reachable only while the feature is
     * switched on (RequiresProfileCompletion), and idempotent — the parent may
     * come back and correct an answer.
     */
    public function saveDetails(SaveProfileDetailsRequest $request): JsonResponse
    {
        $user = $request->user();

        SubscriberProfile::updateOrCreate(
            ['subscriber_id' => $user->id],
            $request->validated() + ['completed_at' => now()]
        );

        return ApiResponse::success(
            data: $this->payload($user->fresh()),
            message: 'تم حفظ البيانات بنجاح'
        );
    }

    /**
     * The profile as the app reads it. The completion block appears only while
     * the feature is on — with it off, no response mentions it at all.
     */
    private function payload($user): array
    {
        $data = (new SubscriberResource($user))->resolve();

        if (! $this->completion->isEnabled()) {
            return $data;
        }

        $profile = $user->profile()->with('governorate')->first();

        return $data + [
            'details' => $profile ? (new SubscriberProfileResource($profile))->resolve() : null,
            'profile_completion' => $this->completion->status($user),
        ];
    }
}
