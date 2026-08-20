<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Auth\LoginSubscriberAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterSubscriberAction;
use App\Actions\Auth\ResendOtpAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\VerifyPhoneAction;
use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\App\LoginRequest;
use App\Http\Requests\Api\V1\App\RegisterRequest;
use App\Http\Requests\Api\V1\App\ResendOtpRequest;
use App\Http\Requests\Api\V1\App\ResetPasswordRequest;
use App\Http\Requests\Api\V1\App\VerifyOtpRequest;
use App\Http\Resources\Api\V1\SubscriberResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterSubscriberAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        $data = ['user' => new SubscriberResource($result['user'])];
        $message = 'تم إنشاء الحساب بنجاح، تم إرسال رمز التحقق إلى رقم هاتفك';

        if ($result['token']) {
            $data['token'] = $result['token'];
            $data['token_type'] = 'Bearer';
            $message = 'تم إنشاء الحساب وتفعيله بنجاح';
        }

        return ApiResponse::success(
            data: $data,
            message: $message,
            status: Response::HTTP_CREATED
        );
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyPhoneAction $action): JsonResponse
    {
        $result = $action->execute(
            phone: $request->validated('phone'),
            code: $request->validated('code')
        );

        return ApiResponse::success(
            data: [
                'user' => new SubscriberResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'تم التحقق من رقم الهاتف وتفعيل الحساب بنجاح'
        );
    }

    public function resendOtp(ResendOtpRequest $request, ResendOtpAction $action): JsonResponse
    {
        $purpose = OtpPurpose::from($request->validated('purpose') ?? OtpPurpose::PHONE_VERIFICATION->value);

        $action->execute(
            phone: $request->validated('phone'),
            purpose: $purpose
        );

        return ApiResponse::success(
            data: null,
            message: 'تم إرسال رمز تحقق جديد إلى رقم هاتفك'
        );
    }

    public function login(LoginRequest $request, LoginSubscriberAction $action): JsonResponse
    {
        $result = $action->execute(
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            deviceName: $request->validated('device_name')
        );

        return ApiResponse::success(
            data: [
                'user' => new SubscriberResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'تم تسجيل الدخول بنجاح'
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request, ResendOtpAction $action): JsonResponse
    {
        $action->execute(
            phone: $request->validated('phone'),
            purpose: OtpPurpose::PASSWORD_RESET
        );

        return ApiResponse::success(
            data: null,
            message: 'تم إرسال رمز التحقق لاستعادة كلمة المرور إلى رقم هاتفك'
        );
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $action->execute(
            phone: $request->validated('phone'),
            code: $request->validated('code'),
            newPassword: $request->validated('password')
        );

        return ApiResponse::success(
            data: null,
            message: 'تم تحديث كلمة المرور بنجاح'
        );
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute($request->user());

        return ApiResponse::success(
            data: null,
            message: 'تم تسجيل الخروج بنجاح'
        );
    }
}
