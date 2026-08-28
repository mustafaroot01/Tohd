<?php

namespace Tests\Feature;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpException;
use App\Models\PhoneVerification;
use App\Models\SystemSetting;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Talking to Arqam for real (HTTP faked). Arqam answers HTTP 200 with
 * {"success": false} when it rejects a code — trusting the status line alone
 * would let ANY code verify ANY phone.
 */
class OtpProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.otp.fake', false);
        SystemSetting::get()->update(['otp_base_url' => 'https://otp.test/api', 'otp_api_key' => 'test-key']);

        PhoneVerification::create([
            'phone' => '+9647700000001',
            'message_id' => 'msg-1',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(3),
        ]);
    }

    public function test_a_wrong_code_is_rejected_even_when_the_provider_answers_http_200(): void
    {
        Http::fake(['*/sms/verify' => Http::response(['success' => false, 'message' => 'Invalid OTP code'], 200)]);

        try {
            app(OtpService::class)->verify('07700000001', OtpPurpose::REGISTER, '000000');
            $this->fail('expected OtpException');
        } catch (OtpException $e) {
            $this->assertSame('INVALID_CODE', $e->reason);
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertNull(PhoneVerification::first()->verified_at);
    }

    public function test_an_expired_provider_message_maps_to_the_expiry_error(): void
    {
        Http::fake(['*/sms/verify' => Http::response(['success' => false, 'message' => 'Code expired'], 200)]);

        $this->expectExceptionMessage('انتهت صلاحية الرمز، اطلب رمزاً جديداً');
        app(OtpService::class)->verify('07700000001', OtpPurpose::REGISTER, '000000');
    }

    public function test_a_correct_code_is_accepted_and_the_row_marked_verified(): void
    {
        Http::fake(['*/sms/verify' => Http::response(['success' => true], 200)]);

        $verification = app(OtpService::class)->verify('07700000001', OtpPurpose::REGISTER, '536156');

        $this->assertNotNull($verification->verified_at);
        Http::assertSent(fn ($req) => $req->url() === 'https://otp.test/api/sms/verify'
            && $req['messageId'] === 'msg-1' && $req['code'] === '536156'
            && $req->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_sending_posts_the_international_number_and_keeps_the_message_id(): void
    {
        Http::fake(['*/sms/otp' => Http::response(['success' => true, 'messageId' => 'msg-9'], 200)]);

        $row = app(OtpService::class)->send('07700000002', OtpPurpose::REGISTER);

        $this->assertSame('msg-9', $row->message_id);
        Http::assertSent(fn ($req) => $req['phoneNumber'] === '9647700000002');
    }

    public function test_a_rejected_send_stores_nothing_and_is_a_service_fault(): void
    {
        Http::fake(['*/sms/otp' => Http::response(['success' => false, 'code' => 'INSUFFICIENT_CREDITS'], 200)]);

        try {
            app(OtpService::class)->send('07700000002', OtpPurpose::REGISTER);
            $this->fail('expected OtpException');
        } catch (OtpException $e) {
            $this->assertTrue($e->isServiceFault());
            $this->assertSame(503, $e->getStatusCode());
        }

        $this->assertFalse(PhoneVerification::where('phone', '+9647700000002')->exists());
    }

    public function test_an_unreachable_provider_is_a_503_not_a_crash(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'فارس', 'phone' => '07700000003', 'password' => 'secret123456', 'password_confirmation' => 'secret123456',
        ])
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'OTP_SERVICE_UNAVAILABLE');
    }

    public function test_missing_credentials_are_reported_not_attempted(): void
    {
        SystemSetting::get()->update(['otp_base_url' => null]);
        Http::fake();

        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'فارس', 'phone' => '07700000003', 'password' => 'secret123456', 'password_confirmation' => 'secret123456',
        ])->assertStatus(503)->assertJsonPath('error_code', 'OTP_NOT_CONFIGURED');

        Http::assertNothingSent();
    }
}
