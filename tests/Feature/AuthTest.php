<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_can_register_and_gets_unverified_status_without_token(): void
    {
        $response = $this->postJson('/api/v1/app/auth/register', [
            'name' => 'فهد البطل',
            'phone' => '07701234567',
            'password' => 'secret123456',
            'address' => 'بغداد',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.user.status', 'UNVERIFIED')
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('subscribers', ['phone' => '+9647701234567', 'status' => 'UNVERIFIED']);

        $this->assertNotNull($this->fakeSmsGateway()->lastCodeFor('+9647701234567'));
    }

    public function test_subscriber_can_verify_otp_and_login_afterwards(): void
    {
        $this->postJson('/api/v1/app/auth/register', [
            'name' => 'سارة',
            'phone' => '07709876543',
            'password' => 'password123',
        ])->assertStatus(201);

        $code = $this->fakeSmsGateway()->lastCodeFor('+9647709876543');

        $verifyRes = $this->postJson('/api/v1/app/auth/otp/verify', [
            'phone' => '07709876543',
            'code' => $code,
        ]);

        $verifyRes->assertStatus(200)
            ->assertJsonPath('data.user.status', 'ACTIVE')
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);

        $loginRes = $this->postJson('/api/v1/app/auth/login', [
            'phone' => '07709876543',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_unverified_subscriber_cannot_login(): void
    {
        Subscriber::create([
            'name' => 'غير موثق',
            'phone' => '+9647701112222',
            'password' => bcrypt('password123'),
            'status' => 'UNVERIFIED',
        ]);

        $response = $this->postJson('/api/v1/app/auth/login', [
            'phone' => '07701112222',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'ACCOUNT_UNVERIFIED');
    }

    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_authenticated_admin_can_fetch_profile_and_logout(): void
    {
        $admin = User::create([
            'name' => 'مدير',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password123'),
            'status' => 'ACTIVE',
        ]);

        $token = $admin->createToken('test_token')->plainTextToken;

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'admin2@example.com');

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
