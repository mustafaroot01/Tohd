<?php

namespace Tests\Feature;

use App\Enums\CurriculumStatus;
use App\Enums\ProductStatus;
use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Product $product;
    protected Curriculum $curriculum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->user = User::create([
            'name' => 'App User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);

        $this->curriculum = Curriculum::create([
            'code' => 'CURR-TEST',
            'name' => 'منهج تجريبي',
            'slug' => 'curr-test',
            'status' => CurriculumStatus::PUBLISHED,
        ]);

        $this->product = Product::create([
            'code' => 'PROD-TEST',
            'name' => 'منتج تجريبي',
            'slug' => 'prod-test',
            'curriculum_id' => $this->curriculum->id,
            'duration_days' => 30,
            'status' => ProductStatus::ACTIVE,
            'price' => 100,
        ]);
    }

    public function test_admin_can_generate_activation_codes(): void
    {
        $adminToken = $this->admin->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/admin/activation-codes/generate', [
                'product_id' => $this->product->id,
                'quantity' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertEquals(3, ActivationCode::where('product_id', $this->product->id)->count());
    }

    public function test_user_can_redeem_activation_code(): void
    {
        $code = ActivationCode::create([
            'code' => 'TEST-1234-5678-ABCD',
            'product_id' => $this->product->id,
            'status' => 'AVAILABLE',
        ]);

        $userToken = $this->user->createToken('user')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->postJson('/api/v1/app/activations/redeem', [
                'code' => 'TEST-1234-5678-ABCD',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('activation_codes', [
            'id' => $code->id,
            'status' => 'ACTIVATED',
            'activated_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('user_curriculum_assignments', [
            'user_id' => $this->user->id,
            'curriculum_id' => $this->curriculum->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_user_cannot_redeem_already_used_code(): void
    {
        $code = ActivationCode::create([
            'code' => 'TEST-USED-1234-ABCD',
            'product_id' => $this->product->id,
            'status' => 'ACTIVATED',
            'activated_by' => $this->admin->id,
        ]);

        $userToken = $this->user->createToken('user')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$userToken)
            ->postJson('/api/v1/app/activations/redeem', [
                'code' => 'TEST-USED-1234-ABCD',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'ACTIVATION_ALREADY_USED');
    }
}
