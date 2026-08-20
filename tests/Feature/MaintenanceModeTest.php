<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_mode_blocks_app_routes_but_not_admin_or_auth(): void
    {
        SystemSetting::get()->update(['is_maintenance' => true]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230030',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
            'phone_verified_at' => now(),
        ]);

        // Subscriber-facing app routes must be rejected with 503.
        $registerRes = $this->postJson('/api/v1/app/auth/register', [
            'name' => 'مستخدم جديد',
            'phone' => '07701230031',
            'password' => 'password123',
        ]);
        $registerRes->assertStatus(503)->assertJsonPath('error_code', 'MAINTENANCE_MODE');

        $subscriberToken = $subscriber->createToken('mobile_app')->plainTextToken;
        $homeRes = $this->withHeader('Authorization', 'Bearer '.$subscriberToken)
            ->getJson('/api/v1/app/home');
        $homeRes->assertStatus(503)->assertJsonPath('error_code', 'MAINTENANCE_MODE');

        // Admin and system-user auth routes must remain accessible.
        $this->app['auth']->forgetGuards();

        $adminLoginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $adminLoginRes->assertStatus(200);

        $this->app['auth']->forgetGuards();

        $adminToken = $admin->createToken('admin')->plainTextToken;
        $dashboardRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/admin/dashboard');
        $dashboardRes->assertStatus(200);
    }

    public function test_app_routes_work_normally_when_maintenance_disabled(): void
    {
        SystemSetting::get()->update(['is_maintenance' => false]);

        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230032',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
            'phone_verified_at' => now(),
        ]);

        $token = $subscriber->createToken('mobile_app')->plainTextToken;
        $homeRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/app/home');
        $homeRes->assertStatus(200);
    }
}
