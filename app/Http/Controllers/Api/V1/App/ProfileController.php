<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Otp\SendOtpAction;
use App\Enums\OtpPurpose;
use App\Enums\SubscriberActivityType;
use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\UpdateProfileRequest;
use App\Http\Resources\Api\V1\SubscriberResource;
use App\Services\SubscriberActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: new SubscriberResource($request->user()),
            message: 'تم استرجاع الملف الشخصي بنجاح'
        );
    }

    public function update(UpdateProfileRequest $request, SendOtpAction $sendOtp, SubscriberActivityLogger $activityLogger): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $phoneChanged = isset($data['phone']) && $data['phone'] !== $user->phone;

        if ($phoneChanged) {
            $data['phone_verified_at'] = null;
            $data['status'] = SubscriberStatus::UNVERIFIED;
        }

        $user->update($data);

        if ($phoneChanged) {
            $activityLogger->log($user, SubscriberActivityType::PHONE_CHANGED, ['new_phone' => $user->phone]);
            $sendOtp->execute($user, $user->phone, OtpPurpose::PHONE_VERIFICATION);
        }

        return ApiResponse::success(
            data: new SubscriberResource($user->fresh()),
            message: $phoneChanged
                ? 'تم تحديث الملف الشخصي، يرجى التحقق من رقم الهاتف الجديد عبر رمز التحقق المُرسَل'
                : 'تم تحديث الملف الشخصي بنجاح'
        );
    }
}
