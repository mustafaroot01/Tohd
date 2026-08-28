<?php

namespace Tests\Feature;

use App\Enums\OtpPurpose;
use App\Models\PhoneVerification;
use App\Models\Subscriber;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Signup is two steps and creates nothing until the WhatsApp code is proven;
 * login is phone + password with no code; password recovery is the second and
 * last place a code is ever sent. (OTP_FAKE is on in phpunit.xml: nothing is
 * sent and 123456 is the code.)
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNUP = [
        'name' => 'فارس الصغير',
        'phone' => '07701234567',
        'password' => 'secret123456',
        'password_confirmation' => 'secret123456',
    ];

    /** The token register hands back; every verify needs it. */
    protected ?string $signupToken = null;

    private function register(array $overrides = [])
    {
        $res = $this->postJson('/api/v1/app/auth/register', array_merge(self::SIGNUP, $overrides));
        $this->signupToken = $res->json('data.signup_token');

        return $res;
    }

    private function verify(string $phone = '07701234567', string $code = OtpService::FAKE_CODE, ?string $token = null)
    {
        return $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => $phone,
            'code' => $code,
            'signup_token' => $token ?? $this->signupToken ?? 'none',
        ]);
    }

    public function test_registering_holds_the_signup_and_sends_a_code_but_creates_no_account(): void
    {
        $this->register()
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '+9647701234567')
            ->assertJsonPath('data.resend_in', 60)
            ->assertJsonMissingPath('data.token');

        $this->assertNotEmpty($this->signupToken);

        $this->assertDatabaseMissing('subscribers', ['phone' => '+9647701234567']);
        $this->assertDatabaseHas('phone_verifications', ['phone' => '+9647701234567', 'purpose' => OtpPurpose::REGISTER->value]);
        $this->assertTrue(Cache::has('signup:+9647701234567'));
    }

    public function test_the_right_code_creates_the_account_active_and_signs_it_in(): void
    {
        $this->register()->assertStatus(201);

        $res = $this->verify()
            ->assertStatus(201)
            ->assertJsonPath('data.user.phone', '+9647701234567')
            ->assertJsonPath('data.user.status', 'ACTIVE')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($res->json('data.token'));

        $subscriber = Subscriber::where('phone', '+9647701234567')->firstOrFail();
        $this->assertNotNull($subscriber->phone_verified_at);
        $this->assertFalse(Cache::has('signup:+9647701234567'));

        // the token works, and login with the password works too
        $this->withHeader('Authorization', 'Bearer '.$res->json('data.token'))
            ->getJson('/api/v1/app/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'فارس الصغير');

        $this->postJson('/api/v1/app/auth/login', ['phone' => '07701234567', 'password' => 'secret123456'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_a_stranger_cannot_finish_a_signup_they_did_not_start(): void
    {
        $this->register()->assertStatus(201);
        $victimToken = $this->signupToken;

        // the attacker re-registers the same number with their own password —
        // refused by the cooldown, and it must not have touched the signup
        $this->travelTo(now()->addSeconds(61));
        $this->register(['name' => 'مهاجم', 'password' => 'attacker-pass', 'password_confirmation' => 'attacker-pass'])
            ->assertStatus(201);
        $attackerToken = $this->signupToken;

        // the victim's code + the victim's token no longer match the pending signup
        $this->verify(token: $victimToken)
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'انتهت جلسة التسجيل، أعد إدخال بياناتك');

        $this->assertDatabaseMissing('subscribers', ['phone' => '+9647701234567']);

        // and a verify with no token at all is refused before anything happens
        $this->postJson('/api/v1/app/auth/otp/verify', ['phone' => '07701234567', 'code' => OtpService::FAKE_CODE])
            ->assertStatus(422)
            ->assertJsonPath('errors.signup_token.0', 'انتهت جلسة التسجيل، أعد إدخال بياناتك');

        // only the holder of the current token can finish it
        $this->verify(token: $attackerToken)->assertStatus(201);
    }

    public function test_a_refused_send_leaves_no_signup_behind(): void
    {
        $this->register()->assertStatus(201);
        $firstToken = $this->signupToken;

        // inside the cooldown: the send is refused, so the pending signup must
        // still be the first one, with its own token
        $this->postJson('/api/v1/app/auth/register', array_merge(self::SIGNUP, [
            'name' => 'مهاجم', 'password' => 'attacker-pass', 'password_confirmation' => 'attacker-pass',
        ]))->assertStatus(422)->assertJsonPath('errors.otp.0', 'COOLDOWN');

        $this->verify(token: $firstToken)
            ->assertStatus(201)
            ->assertJsonPath('data.user.name', 'فارس الصغير');
    }

    public function test_a_phone_registered_meanwhile_is_refused_at_verify(): void
    {
        $this->register()->assertStatus(201);

        Subscriber::create(['name' => 'أنشأه المشرف', 'phone' => '+9647701234567', 'password' => bcrypt('x'), 'status' => 'ACTIVE', 'phone_verified_at' => now()]);

        $this->verify()
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'رقم الهاتف مسجّل بالفعل، سجّل الدخول أو استعد كلمة المرور');

        $this->assertSame(1, Subscriber::where('phone', '+9647701234567')->count());
    }

    public function test_a_malformed_phone_is_a_validation_error_not_a_crash(): void
    {
        // whatever a client sends must reach the validator, never the normaliser
        foreach ([['x'], ['a' => 'b'], 123, true] as $phone) {
            foreach (['register', 'otp/resend', 'password/forgot', 'otp/verify', 'password/reset', 'login'] as $route) {
                $status = $this->postJson("/api/v1/app/auth/{$route}", [
                    'phone' => $phone, 'code' => '123456', 'password' => 'secret123456',
                    'password_confirmation' => 'secret123456', 'name' => 'فارس', 'signup_token' => 'x',
                ])->status();

                $this->assertLessThan(500, $status, "POST {$route} crashed on a malformed phone");
            }
        }
    }

    public function test_a_wrong_code_is_refused_and_still_creates_nothing(): void
    {
        $this->register()->assertStatus(201);

        $this->verify(code: '000000')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'OTP_INVALID_CODE')
            ->assertJsonPath('errors.otp.0', 'INVALID_CODE');

        $this->assertDatabaseMissing('subscribers', ['phone' => '+9647701234567']);
    }

    public function test_a_code_that_is_not_six_digits_is_rejected_before_anything_else(): void
    {
        $this->register()->assertStatus(201);

        $this->verify(code: '12')
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'الرمز يتكوّن من ٦ أرقام');
    }

    public function test_a_signup_session_expires_after_ten_minutes(): void
    {
        $this->register()->assertStatus(201);

        $this->travelTo(now()->addMinutes(11));

        $this->verify()
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'انتهت جلسة التسجيل، أعد إدخال بياناتك');
    }

    public function test_an_expired_code_is_refused_even_inside_the_signup_session(): void
    {
        $this->register()->assertStatus(201);

        $this->travelTo(now()->addMinutes(4)); // code lives 3 minutes, signup 10

        $this->verify()
            ->assertStatus(422)
            ->assertJsonPath('errors.otp.0', 'EXPIRED_CODE');
    }

    public function test_a_phone_that_already_has_an_account_cannot_register_again(): void
    {
        Subscriber::create(['name' => 'موجود', 'phone' => '+9647701234567', 'password' => bcrypt('x'), 'status' => 'ACTIVE', 'phone_verified_at' => now()]);

        $this->register()
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'رقم الهاتف مسجّل بالفعل، سجّل الدخول أو استعد كلمة المرور');
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->register(['password_confirmation' => 'different'])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'تأكيد كلمة المرور غير مطابق');
    }

    public function test_resend_is_refused_inside_the_cooldown_and_allowed_after_it(): void
    {
        $this->register()->assertStatus(201);

        $this->postJson('/api/v1/app/auth/otp/resend', ['phone' => '07701234567'])
            ->assertStatus(422)
            ->assertJsonPath('errors.otp.0', 'COOLDOWN');

        $this->travelTo(now()->addSeconds(61));

        $this->postJson('/api/v1/app/auth/otp/resend', ['phone' => '07701234567'])
            ->assertStatus(200)
            ->assertJsonPath('data.purpose', 'register')
            ->assertJsonPath('data.resend_in', 60);

        $this->assertSame(2, PhoneVerification::where('phone', '+9647701234567')->count());
    }

    public function test_resend_for_an_existing_account_is_a_password_recovery_code(): void
    {
        Subscriber::create(['name' => 'موجود', 'phone' => '+9647701234567', 'password' => bcrypt('x'), 'status' => 'ACTIVE', 'phone_verified_at' => now()]);

        $this->postJson('/api/v1/app/auth/otp/resend', ['phone' => '07701234567'])
            ->assertStatus(200)
            ->assertJsonPath('data.purpose', 'reset');

        $this->assertDatabaseHas('phone_verifications', ['phone' => '+9647701234567', 'purpose' => OtpPurpose::RESET->value]);
    }

    public function test_resend_for_an_unknown_phone_is_refused(): void
    {
        $this->postJson('/api/v1/app/auth/otp/resend', ['phone' => '07709999999'])
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'لا يوجد حساب بهذا الرقم');
    }

    public function test_forgot_and_reset_password_via_a_code_then_login_with_the_new_password(): void
    {
        $subscriber = Subscriber::create(['name' => 'موجود', 'phone' => '+9647701234567', 'password' => bcrypt('old-password-1'), 'status' => 'ACTIVE', 'phone_verified_at' => now()]);
        $oldToken = $subscriber->createToken('mobile_app')->plainTextToken;

        $this->postJson('/api/v1/app/auth/password/forgot', ['phone' => '07701234567'])
            ->assertStatus(200)
            ->assertJsonPath('data.resend_in', 60);

        $this->postJson('/api/v1/app/auth/password/reset', [
            'phone' => '07701234567',
            'code' => OtpService::FAKE_CODE,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertStatus(200);

        // every old session died with the old password
        $this->withHeader('Authorization', 'Bearer '.$oldToken)->getJson('/api/v1/app/profile')->assertStatus(401);

        $this->postJson('/api/v1/app/auth/login', ['phone' => '07701234567', 'password' => 'old-password-1'])->assertStatus(401);
        $this->postJson('/api/v1/app/auth/login', ['phone' => '07701234567', 'password' => 'new-password-1'])->assertStatus(200);
    }

    public function test_a_suspended_account_gets_no_recovery_code(): void
    {
        Subscriber::create(['name' => 'موقوف', 'phone' => '+9647701234567', 'password' => bcrypt('x'), 'status' => 'SUSPENDED', 'phone_verified_at' => now()]);

        $this->postJson('/api/v1/app/auth/password/forgot', ['phone' => '07701234567'])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ACCOUNT_SUSPENDED');

        $this->assertDatabaseMissing('phone_verifications', ['phone' => '+9647701234567']);
    }

    public function test_with_otp_switched_off_the_account_is_created_and_signed_in_at_once(): void
    {
        SystemSetting::get()->update(['otp_enabled' => false]);

        $res = $this->register()
            ->assertStatus(201)
            ->assertJsonPath('data.user.status', 'ACTIVE');

        $this->assertNotEmpty($res->json('data.token'));
        $this->assertDatabaseHas('subscribers', ['phone' => '+9647701234567']);
        $this->assertDatabaseMissing('phone_verifications', ['phone' => '+9647701234567']);
    }

    public function test_login_never_sends_a_code(): void
    {
        Subscriber::create(['name' => 'موجود', 'phone' => '+9647701234567', 'password' => bcrypt('secret123456'), 'status' => 'ACTIVE', 'phone_verified_at' => now()]);

        $this->postJson('/api/v1/app/auth/login', ['phone' => '07701234567', 'password' => 'secret123456'])->assertStatus(200);

        $this->assertSame(0, PhoneVerification::count());
    }

    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@test.com', 'password' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_authenticated_admin_can_fetch_profile_and_logout(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'a@test.com', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $token = $admin->createToken('a')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me')->assertStatus(200);
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/logout')->assertStatus(200);
    }
}
