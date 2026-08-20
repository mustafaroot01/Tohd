<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_level_throttle_returns_the_standard_json_error_envelope(): void
    {
        // otp/resend is route-throttled at 5 requests/minute. A non-existent
        // phone keeps each request cheap (404 PHONE_NOT_FOUND) so the 6th
        // request trips the framework-level throttle middleware itself,
        // before ever reaching the controller/action.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/app/auth/otp/resend', ['phone' => '07709999999'])
                ->assertStatus(404);
        }

        $response = $this->postJson('/api/v1/app/auth/otp/resend', ['phone' => '07709999999']);

        $response->assertStatus(429)
            ->assertJsonStructure(['success', 'message', 'error_code', 'errors'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }
}
