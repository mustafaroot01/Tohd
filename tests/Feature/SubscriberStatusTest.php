<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_suspend_and_reactivate_subscriber(): void
    {
        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230010',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $adminToken = $this->admin->createToken('admin')->plainTextToken;

        $suspendRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/subscribers/{$subscriber->id}/suspend");
        $suspendRes->assertStatus(200)->assertJsonPath('data.status', 'SUSPENDED');

        // Sanctum's guard caches the resolved user for the lifetime of the test's
        // container, so switching bearer tokens mid-test requires clearing it.
        $this->app['auth']->forgetGuards();

        $subscriberToken = $subscriber->createToken('mobile_app')->plainTextToken;
        $homeRes = $this->withHeader('Authorization', 'Bearer '.$subscriberToken)
            ->getJson('/api/v1/app/home');
        $homeRes->assertStatus(403)->assertJsonPath('error_code', 'ACCOUNT_SUSPENDED');

        $this->app['auth']->forgetGuards();

        $reactivateRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/subscribers/{$subscriber->id}/reactivate");
        $reactivateRes->assertStatus(200)->assertJsonPath('data.status', 'ACTIVE');

        $this->app['auth']->forgetGuards();

        $homeResAfter = $this->withHeader('Authorization', 'Bearer '.$subscriberToken)
            ->getJson('/api/v1/app/home');
        $homeResAfter->assertStatus(200);
    }

    public function test_suspended_subscriber_cannot_login(): void
    {
        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230011',
            'password' => bcrypt('password123'),
            'status' => 'SUSPENDED',
        ]);

        $response = $this->postJson('/api/v1/app/auth/login', [
            'phone' => '07701230011',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)->assertJsonPath('error_code', 'ACCOUNT_SUSPENDED');
    }

    public function test_admin_updating_subscriber_phone_is_trusted_and_verified(): void
    {
        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230012',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
            'phone_verified_at' => now(),
        ]);

        $adminToken = $this->admin->createToken('admin')->plainTextToken;

        $updateRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson("/api/v1/admin/subscribers/{$subscriber->id}", [
                'phone' => '07701230099',
                'status' => 'ACTIVE',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonPath('data.phone', '+9647701230099');

        $this->assertNotNull($subscriber->fresh()->phone_verified_at);
        $this->assertDatabaseMissing('phone_verifications', ['phone' => '+9647701230099']);
    }
}
