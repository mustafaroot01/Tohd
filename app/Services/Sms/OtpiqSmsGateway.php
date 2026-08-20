<?php

namespace App\Services\Sms;

use App\Contracts\SmsGatewayInterface;
use App\Exceptions\OtpDeliveryFailedException;
use App\Models\SystemSetting;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OtpiqSmsGateway implements SmsGatewayInterface
{
    public function send(string $e164Phone, string $message): void
    {
        $settings = SystemSetting::get();

        $payload = [
            'phoneNumber' => PhoneNumber::digitsOnly($e164Phone),
            'smsType' => 'verification',
            'verificationCode' => $message,
        ];

        if ($provider = config('otp.otpiq.provider')) {
            $payload['provider'] = $provider;
        }

        try {
            $response = Http::withToken($settings->resolvedOtpApiKey())
                ->timeout(10)
                ->post(config('otp.otpiq.base_url'), $payload);
        } catch (Throwable $e) {
            Log::error('OTPIQ SMS gateway connection failed', ['error' => $e->getMessage(), 'phone' => $e164Phone]);

            throw new OtpDeliveryFailedException;
        }

        if (! $response->successful()) {
            Log::error('OTPIQ SMS gateway rejected the request', [
                'status' => $response->status(),
                'body' => $response->json(),
                'phone' => $e164Phone,
            ]);

            throw new OtpDeliveryFailedException;
        }
    }
}
