<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Rules\PhoneNumberRule;
use App\Services\OtpService;
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
        return ApiResponse::success(
            data: SystemSetting::get(),
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
            'otp_base_url' => ['nullable', 'url', 'max:255'],
            'otp_api_key' => ['nullable', 'string', 'max:255'],
            'profile_completion_enabled' => ['required', 'boolean'],
        ]);

        if ($request->hasFile('app_logo_file')) {
            if ($settings->app_logo) {
                Storage::disk('public')->delete($settings->app_logo);
            }

            $settings->app_logo = $request->file('app_logo_file')->store('settings', 'public');
        }

        $settings->fill([
            'app_name' => $validated['app_name'],
            'is_maintenance' => $validated['is_maintenance'],
            'otp_enabled' => $validated['otp_enabled'],
            'otp_base_url' => $validated['otp_base_url'] ?? $settings->otp_base_url,
            'profile_completion_enabled' => $validated['profile_completion_enabled'],
        ]);

        // The raw key is never sent back to the client, so the form field is
        // submitted blank unless the admin is deliberately setting a new one —
        // blank means "keep the existing key", not "clear it".
        if (filled($validated['otp_api_key'] ?? null)) {
            $settings->otp_api_key = $validated['otp_api_key'];
        }

        $settings->save();

        SystemSetting::forgetCached();

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
     * Send a real code through the saved Arqam credentials, so the admin can
     * confirm the key works without going through a registration.
     */
    public function testSms(Request $request, OtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', new PhoneNumberRule],
        ]);

        $otp->send(PhoneNumber::normalize($validated['phone']), OtpPurpose::REGISTER);

        return ApiResponse::success(
            message: 'تم إرسال رمز اختبار عبر واتساب، تحقق من وصوله إلى الرقم المُدخل'
        );
    }
}
