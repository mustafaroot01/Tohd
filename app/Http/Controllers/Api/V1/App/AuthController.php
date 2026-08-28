<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Auth\LoginSubscriberAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterSubscriberAction;
use App\Actions\Auth\ResendOtpAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\VerifyPhoneAction;
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

/**
 * A WhatsApp code is sent in exactly two cases: to prove the phone once at
 * signup, and before a password is replaced. Login is phone + password only.
 *
 *   register ──code──▶ otp/verify ──▶ account is created and signed in
 *   login: phone + password ──▶ token          (no code)
 *   password/forgot ──code──▶ password/reset ──▶ log in again
 */
class AuthController extends Controller
{
    /**
     * Holds the signup and sends the code. No account exists until the code
     * is proven — unless OTP is switched off, in which case the account is
     * created and signed in right away.
     */
    public function register(RegisterRequest $request, RegisterSubscriberAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (isset($result['user'])) {
            return $this->authPayload($result, 'تم إنشاء الحساب وتفعيله بنجاح', Response::HTTP_CREATED);
        }

        return ApiResponse::success(
            data: $result,
            message: 'أرسلنا رمز التحقق إلى واتساب',
            status: Response::HTTP_CREATED
        );
    }

    /** The code proved the phone: the account is created and signed in. */
    public function verifyOtp(VerifyOtpRequest $request, VerifyPhoneAction $action): JsonResponse
    {
        $result = $action->execute(
            phone: $request->validated('phone'),
            code: $request->validated('code'),
            signupToken: $request->validated('signup_token')
        );

        return $this->authPayload($result, 'تم إنشاء حسابك بنجاح', Response::HTTP_CREATED);
    }

    /** "لم يصلني الرمز" — for both the signup and the recovery screens. */
    public function resendOtp(ResendOtpRequest $request, ResendOtpAction $action): JsonResponse
    {
        return ApiResponse::success(
            data: $action->execute($request->validated('phone')),
            message: 'تم إرسال رمز جديد'
        );
    }

    public function login(LoginRequest $request, LoginSubscriberAction $action): JsonResponse
    {
        $result = $action->execute(
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            deviceName: $request->validated('device_name')
        );

        return $this->authPayload($result, 'تم تسجيل الدخول بنجاح');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ResendOtpAction $action): JsonResponse
    {
        return ApiResponse::success(
            data: $action->sendReset($request->validated('phone')),
            message: 'أرسلنا رمز استعادة كلمة المرور إلى واتساب'
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
            message: 'تم تغيير كلمة المرور، سجّل الدخول بكلمتك الجديدة'
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

    private function authPayload(array $result, string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        return ApiResponse::success(
            data: [
                'user' => new SubscriberResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: $message,
            status: $status
        );
    }
}
