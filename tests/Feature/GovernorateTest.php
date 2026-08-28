<?php

namespace Tests\Feature;

use App\Models\Governorate;
use App\Models\Subscriber;
use App\Models\SubscriberProfile;
use App\Models\User;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The governorate list the dashboard owns: add, rename, hide. A governorate
 * someone already chose is hidden, never deleted.
 */
class GovernorateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'gov@test.com', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        $this->withHeader('Authorization', 'Bearer '.$this->admin->createToken('a')->plainTextToken);
    }

    public function test_the_seeder_installs_the_eighteen_iraqi_governorates(): void
    {
        $this->seed(GovernorateSeeder::class);

        $this->assertSame(18, Governorate::count());
        $this->assertTrue(Governorate::whereIn('name', ['بغداد', 'نينوى', 'البصرة', 'دهوك', 'المثنى', 'ديالى'])->count() === 6);

        // running it twice must not duplicate anything
        $this->seed(GovernorateSeeder::class);
        $this->assertSame(18, Governorate::count());
    }

    public function test_admin_can_list_add_and_rename_a_governorate(): void
    {
        Governorate::create(['name' => 'بغداد', 'code' => 'BGD', 'sort_order' => 1]);

        $this->getJson('/api/v1/admin/governorates')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'بغداد')
            ->assertJsonPath('data.0.profiles_count', 0)
            ->assertJsonPath('meta.total', 1);

        $created = $this->postJson('/api/v1/admin/governorates', ['name' => 'البصرة', 'code' => 'BSR', 'sort_order' => 2])
            ->assertStatus(201)
            ->assertJsonPath('data.is_active', true)
            ->json('data.id');

        $this->putJson("/api/v1/admin/governorates/{$created}", ['name' => 'البصرة الفيحاء'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'البصرة الفيحاء');

        $this->postJson('/api/v1/admin/governorates', ['name' => 'بغداد'])
            ->assertStatus(422);
    }

    public function test_hiding_a_governorate_is_a_single_field_update(): void
    {
        $governorate = Governorate::create(['name' => 'بغداد', 'code' => 'BGD']);

        $this->putJson("/api/v1/admin/governorates/{$governorate->id}", ['is_active' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.name', 'بغداد');
    }

    public function test_a_governorate_in_use_cannot_be_deleted_only_hidden(): void
    {
        $governorate = Governorate::create(['name' => 'بغداد', 'code' => 'BGD']);
        $subscriber = Subscriber::create(['name' => 'ولي أمر', 'phone' => '+9647701230001', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        SubscriberProfile::create(['subscriber_id' => $subscriber->id, 'governorate_id' => $governorate->id]);

        $this->deleteJson("/api/v1/admin/governorates/{$governorate->id}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'GOVERNORATE_IN_USE');

        $this->assertSame(1, Governorate::count());
    }

    public function test_an_unused_governorate_can_be_deleted(): void
    {
        $governorate = Governorate::create(['name' => 'بغداد', 'code' => 'BGD']);

        $this->deleteJson("/api/v1/admin/governorates/{$governorate->id}")->assertStatus(200);

        $this->assertSame(0, Governorate::count());
    }

    public function test_a_subscriber_token_cannot_reach_the_admin_list(): void
    {
        $subscriber = Subscriber::create(['name' => 'ولي أمر', 'phone' => '+9647701230002', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);

        $this->withHeader('Authorization', 'Bearer '.$subscriber->createToken('m')->plainTextToken)
            ->getJson('/api/v1/admin/governorates')
            ->assertStatus(403);
    }
}
