<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Subscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin User (instance of User model)
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        // Create Subscriber (instance of Subscriber model)
        $this->subscriber = Subscriber::create([
            'name' => 'Subscriber User',
            'phone' => '123456789',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_retrieve_system_settings(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'app_name',
                    'app_logo_url',
                    'is_maintenance',
                    'otp_enabled',
                    'otp_expiry_minutes',
                    'otp_api_key_configured',
                    'otp_api_key_preview',
                ],
            ])
            ->assertJsonMissingPath('data.otp_api_key');
    }

    public function test_non_admin_cannot_retrieve_system_settings(): void
    {
        // Subscribers are protected by EnsureIsAdmin when accessing /admin routes
        $response = $this->actingAs($this->subscriber, 'sanctum')
            ->getJson('/api/v1/admin/settings');

        $response->assertStatus(403);
    }

    public function test_admin_can_update_system_settings_and_upload_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings', [
                'app_name' => 'رحلة فارس الجديدة',
                'app_logo_file' => $logo,
                'is_maintenance' => true,
                'otp_enabled' => false,
                'otp_expiry_minutes' => 10,
                'otp_api_key' => 'sk_live_secret123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.otp_api_key_configured', true)
            ->assertJsonPath('data.otp_api_key_preview', '••••t123')
            ->assertJsonMissingPath('data.otp_api_key');

        $settings = SystemSetting::get();
        $this->assertEquals('رحلة فارس الجديدة', $settings->app_name);
        $this->assertTrue($settings->is_maintenance);
        $this->assertFalse($settings->otp_enabled);
        $this->assertEquals(10, $settings->otp_expiry_minutes);
        $this->assertEquals('sk_live_secret123', $settings->getRawOriginal('otp_api_key'));

        // Verify Logo uploaded
        $this->assertNotNull($settings->app_logo);
        Storage::disk('public')->assertExists($settings->app_logo);
    }

    public function test_leaving_otp_api_key_blank_keeps_the_existing_key(): void
    {
        SystemSetting::get()->update(['otp_api_key' => 'sk_live_original']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings', [
                'app_name' => 'رحلة فارس',
                'is_maintenance' => false,
                'otp_enabled' => true,
                'otp_expiry_minutes' => 5,
                'otp_api_key' => '',
            ]);

        $response->assertStatus(200);

        $this->assertEquals('sk_live_original', SystemSetting::get()->getRawOriginal('otp_api_key'));
    }

    public function test_admin_can_send_a_test_sms_using_configured_credentials(): void
    {
        SystemSetting::get()->update(['otp_api_key' => 'sk_live_original']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/test-sms', [
                'phone' => '07701234567',
            ]);

        $response->assertStatus(200);

        $this->assertNotNull($this->fakeSmsGateway()->lastCodeFor('+9647701234567'));
    }

    public function test_test_sms_rejects_invalid_phone_number(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/test-sms', [
                'phone' => '12345',
            ]);

        $response->assertStatus(422);
    }

    public function test_guest_can_access_public_settings(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'app_name',
                    'app_logo_url',
                    'is_maintenance',
                    'otp_enabled',
                ],
            ]);
    }
}
