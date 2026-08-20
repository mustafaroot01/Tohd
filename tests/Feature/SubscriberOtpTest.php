<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_skips_otp_and_activates_immediately_when_otp_disabled(): void
    {
        SystemSetting::get()->update(['otp_enabled' => false]);

        $response = $this->postJson('/api/v1/app/auth/register', [
            'name' => 'محمد',
            'phone' => '07701230006',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.status', 'ACTIVE')
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);

        $this->assertNull($this->fakeSmsGateway()->lastCodeFor('+9647701230006'));

        $this->assertDatabaseHas('subscribers', [
            'phone' => '+9647701230006',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_resend_otp_before_cooldown_is_rejected(): void
    {
        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'محمد',
            'phone' => '07701230001',
            'password' => 'password123',
        ])->assertStatus(201);

        $resendRes = $this->postJson('/api/v1/app/auth/otp/resend', [
            'phone' => '07701230001',
        ]);

        $resendRes->assertStatus(429)
            ->assertJsonPath('error_code', 'OTP_RESEND_COOLDOWN');
    }

    public function test_wrong_otp_code_is_rejected_and_increments_attempts(): void
    {
        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'محمد',
            'phone' => '07701230002',
            'password' => 'password123',
        ])->assertStatus(201);

        $response = $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => '07701230002',
            'code' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'OTP_INVALID');

        $this->assertDatabaseHas('otps', ['phone' => '+9647701230002', 'attempts' => 1]);
    }

    public function test_otp_attempts_exceeded_blocks_further_verification(): void
    {
        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'محمد',
            'phone' => '07701230003',
            'password' => 'password123',
        ])->assertStatus(201);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/app/auth/otp/verify', [
                'phone' => '07701230003',
                'code' => '000000',
            ])->assertStatus(422);
        }

        $correctCode = $this->fakeSmsGateway()->lastCodeFor('+9647701230003');

        $response = $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => '07701230003',
            'code' => $correctCode,
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error_code', 'OTP_ATTEMPTS_EXCEEDED');
    }

    public function test_expired_otp_is_rejected(): void
    {
        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'محمد',
            'phone' => '07701230004',
            'password' => 'password123',
        ])->assertStatus(201);

        $code = $this->fakeSmsGateway()->lastCodeFor('+9647701230004');

        $this->travel(6)->minutes();

        $response = $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => '07701230004',
            'code' => $code,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'OTP_EXPIRED');
    }

    public function test_forgot_password_and_reset_via_otp(): void
    {
        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'محمد',
            'phone' => '07701230005',
            'password' => 'password123',
        ])->assertStatus(201);

        $verifyCode = $this->fakeSmsGateway()->lastCodeFor('+9647701230005');
        $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => '07701230005',
            'code' => $verifyCode,
        ])->assertStatus(200);

        $this->postJson('/api/v1/app/auth/password/forgot', [
            'phone' => '07701230005',
        ])->assertStatus(200);

        $resetCode = $this->fakeSmsGateway()->lastCodeFor('+9647701230005');

        $this->postJson('/api/v1/app/auth/password/reset', [
            'phone' => '07701230005',
            'code' => $resetCode,
            'password' => 'newpassword456',
        ])->assertStatus(200);

        $this->postJson('/api/v1/app/auth/login', [
            'phone' => '07701230005',
            'password' => 'newpassword456',
        ])->assertStatus(200)->assertJsonPath('success', true);
    }
}
