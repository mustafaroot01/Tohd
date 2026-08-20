<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Contracts\SmsGatewayInterface;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Rules\PhoneNumberRule;
use App\Support\ApiResponse;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings (Admin only).
     */
    public function index(): JsonResponse
    {
        $settings = SystemSetting::get();

        return ApiResponse::success(
            data: $settings,
            message: 'تم استرجاع إعدادات النظام بنجاح'
        );
    }

    /**
     * Update system settings (Admin only).
     */
    public function update(Request $request): JsonResponse
    {
        $settings = SystemSetting::get();

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_logo_file' => ['nullable', 'image', 'max:2048'], // Max 2MB image
            'is_maintenance' => ['required', 'boolean'],
            'otp_enabled' => ['required', 'boolean'],
            'otp_expiry_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'otp_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        // Process App Logo upload if present
        if ($request->hasFile('app_logo_file')) {
            // Delete old logo file if exists
            if ($settings->app_logo) {
                Storage::disk('public')->delete($settings->app_logo);
            }

            // Store new logo
            $path = $request->file('app_logo_file')->store('settings', 'public');
            $settings->app_logo = $path;
        }

        $settings->fill([
            'app_name' => $validated['app_name'],
            'is_maintenance' => $validated['is_maintenance'],
            'otp_enabled' => $validated['otp_enabled'],
            'otp_expiry_minutes' => $validated['otp_expiry_minutes'],
        ]);

        // The raw key is never sent back to the client, so the form field is
        // submitted blank unless the admin is deliberately setting a new one —
        // blank means "keep the existing key", not "clear it".
        if (filled($validated['otp_api_key'] ?? null)) {
            $settings->otp_api_key = $validated['otp_api_key'];
        }

        $settings->save();

        return ApiResponse::success(
            data: $settings->fresh(),
            message: 'تم تحديث إعدادات النظام وتخصيص الهوية بنجاح'
        );
    }

    /**
     * Get public settings (Guest accessible).
     */
    public function getPublicSettings(): JsonResponse
    {
        $settings = SystemSetting::get();

        return ApiResponse::success(
            data: [
                'app_name' => $settings->app_name,
                'app_logo_url' => $settings->app_logo_url,
                'is_maintenance' => $settings->is_maintenance,
                'otp_enabled' => $settings->otp_enabled,
            ],
            message: 'تم استرجاع الإعدادات العامة للمنصة'
        );
    }

    /**
     * Send a real test SMS through the currently saved OTPIQ credentials, so
     * the admin can confirm the API key works without going through the full
     * registration/OTP flow.
     */
    public function testSms(Request $request, SmsGatewayInterface $gateway): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', new PhoneNumberRule],
        ]);

        $phone = PhoneNumber::normalize($validated['phone']);
        $testCode = (string) random_int(100000, 999999);

        $gateway->send($phone, $testCode);

        return ApiResponse::success(
            message: 'تم إرسال رسالة اختبار بنجاح، تحقق من وصولها إلى الرقم المُدخل'
        );
    }
}
