<?php

namespace App\Actions\Otp;

use App\Enums\OtpPurpose;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpAttemptsExceededException;
use App\Exceptions\OtpExpiredException;
use App\Models\Otp;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Hash;

class VerifyOtpAction
{
    /**
     * Verify an OTP code for the given phone/purpose and mark it consumed.
     *
     * @throws OtpExpiredException
     * @throws OtpAttemptsExceededException
     * @throws InvalidOtpException
     */
    public function execute(Subscriber $subscriber, string $phone, string $code, OtpPurpose $purpose): void
    {
        $otp = Otp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if (! $otp || $otp->isExpired()) {
            throw new OtpExpiredException;
        }

        if ($otp->attempts >= config('otp.max_verify_attempts')) {
            throw new OtpAttemptsExceededException;
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            throw new InvalidOtpException;
        }

        $otp->update(['consumed_at' => now()]);
    }
}
