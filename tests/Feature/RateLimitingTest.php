<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNUP = [
        'name' => 'فارس', 'password' => 'secret123456', 'password_confirmation' => 'secret123456',
    ];

    public function test_code_sends_are_capped_per_phone_with_the_standard_error_envelope(): void
    {
        // otp-send allows four sends per phone per ten minutes; the cooldown
        // refuses the repeats but each request still counts against the cap
        foreach (range(1, 4) as $_) {
            $this->postJson('/api/v1/app/auth/register', self::SIGNUP + ['phone' => '07709999999']);
        }

        $this->postJson('/api/v1/app/auth/register', self::SIGNUP + ['phone' => '07709999999'])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');

        // another phone is unaffected
        $this->postJson('/api/v1/app/auth/register', self::SIGNUP + ['phone' => '07708888888'])->assertStatus(201);
    }

    public function test_code_guesses_are_capped_per_phone(): void
    {
        $token = $this->postJson('/api/v1/app/auth/register', self::SIGNUP + ['phone' => '07709999999'])
            ->assertStatus(201)
            ->json('data.signup_token');

        // real guesses: the payload is valid, only the code is wrong
        foreach (range(1, 8) as $_) {
            $this->postJson('/api/v1/app/auth/otp/verify', ['phone' => '07709999999', 'code' => '000000', 'signup_token' => $token])
                ->assertStatus(422)
                ->assertJsonPath('errors.otp.0', 'INVALID_CODE');
        }

        $this->postJson('/api/v1/app/auth/otp/verify', ['phone' => '07709999999', 'code' => '123456', 'signup_token' => $token])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }
}
