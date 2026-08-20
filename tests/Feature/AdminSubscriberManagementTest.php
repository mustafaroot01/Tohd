<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriberManagementTest extends TestCase
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

    public function test_admin_can_create_a_subscriber_via_http(): void
    {
        $adminToken = $this->admin->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/admin/subscribers', [
                'name' => 'مشترك جديد',
                'phone' => '07701230050',
                'password' => 'password123',
                'address' => 'بغداد',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'مشترك جديد')
            ->assertJsonPath('data.status', 'ACTIVE');

        $this->assertDatabaseHas('subscribers', [
            'phone' => '+9647701230050',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_cannot_create_subscriber_with_duplicate_phone(): void
    {
        Subscriber::create([
            'name' => 'موجود',
            'phone' => '+9647701230051',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $adminToken = $this->admin->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/admin/subscribers', [
                'name' => 'تكرار',
                'phone' => '07701230051',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_admin_can_update_subscriber_name_and_address_without_touching_phone(): void
    {
        $subscriber = Subscriber::create([
            'name' => 'الاسم القديم',
            'phone' => '+9647701230052',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
            'phone_verified_at' => now(),
        ]);

        $adminToken = $this->admin->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson("/api/v1/admin/subscribers/{$subscriber->id}", [
                'name' => 'الاسم الجديد',
                'address' => 'البصرة',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'الاسم الجديد')
            ->assertJsonPath('data.status', 'ACTIVE');

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscriber->id,
            'name' => 'الاسم الجديد',
            'address' => 'البصرة',
            'phone' => '+9647701230052',
            'status' => 'ACTIVE',
        ]);
    }
}
