<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ProductController, UserController and AdminProfileController — three admin
 * surfaces that previously had no coverage at all.
 */
class AdminProductAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $token;

    protected Curriculum $curriculum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'products@test.com',
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);

        $this->token = $this->admin->createToken('admin')->plainTextToken;

        $this->curriculum = Curriculum::create([
            'code' => 'CUR-1', 'name' => 'منهج', 'slug' => 'cur-1',
            'status' => 'PUBLISHED', 'version' => 1,
        ]);
    }

    private function asAdmin(): self
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token);

        return $this;
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'code' => 'P-'.uniqid(),
            'name' => 'باقة',
            'slug' => 'p-'.uniqid(),
            'curriculum_id' => $this->curriculum->id,
            'duration_days' => 30,
            'price' => 50000,
            'status' => 'ACTIVE',
        ], $overrides));
    }

    public function test_admin_can_create_and_update_a_product(): void
    {
        $created = $this->asAdmin()->postJson('/api/v1/admin/products', [
            'code' => 'PROD-001',
            'name' => 'باقة التأسيس',
            'curriculum_id' => $this->curriculum->id,
            'duration_days' => 60,
            'price' => 75000,
        ]);

        $created->assertStatus(201)
            ->assertJsonPath('data.name', 'باقة التأسيس')
            ->assertJsonPath('data.duration_days', 60)
            ->assertJsonPath('data.currency', 'IQD');

        $id = $created->json('data.id');

        $this->asAdmin()->putJson("/api/v1/admin/products/{$id}", ['price' => 90000])
            ->assertStatus(200)
            ->assertJsonPath('data.price', 90000);
    }

    public function test_product_can_be_deactivated_and_reactivated(): void
    {
        $product = $this->makeProduct();

        $this->asAdmin()->postJson("/api/v1/admin/products/{$product->id}/deactivate")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'INACTIVE');

        $this->asAdmin()->postJson("/api/v1/admin/products/{$product->id}/activate")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ACTIVE');
    }

    public function test_product_list_filters_by_status_and_curriculum(): void
    {
        $this->makeProduct(['status' => 'ACTIVE']);
        $this->makeProduct(['status' => 'INACTIVE']);

        $this->asAdmin()->getJson('/api/v1/admin/products?status=INACTIVE')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'INACTIVE');

        $this->asAdmin()->getJson("/api/v1/admin/products?curriculum_id={$this->curriculum->id}")
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_product_resource_exposes_a_consistent_envelope(): void
    {
        $this->makeProduct();

        $res = $this->asAdmin()->getJson('/api/v1/admin/products');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [['id', 'code', 'name', 'price', 'currency', 'status', 'created_at', 'updated_at']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        $this->assertIsString($res->json('data.0.status'));
    }

    public function test_system_user_list_is_readable_and_searchable(): void
    {
        User::create(['name' => 'Second', 'email' => 'second@test.com', 'password' => Hash::make('x'), 'status' => 'ACTIVE']);

        $this->asAdmin()->getJson('/api/v1/admin/users')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        $this->asAdmin()->getJson('/api/v1/admin/users?search=second')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', 'second@test.com');
    }

    public function test_user_resource_never_exposes_the_password_hash(): void
    {
        $payload = $this->asAdmin()->getJson('/api/v1/admin/users')->json('data.0');

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
    }

    public function test_admin_can_read_and_update_own_profile(): void
    {
        $this->asAdmin()->getJson('/api/v1/admin/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'products@test.com');

        $this->asAdmin()->putJson('/api/v1/admin/profile', [
            'name' => 'المدير العام',
            'email' => 'products@test.com',
        ])->assertStatus(200)->assertJsonPath('data.name', 'المدير العام');
    }

    public function test_admin_password_change_requires_the_current_password(): void
    {
        $this->asAdmin()->putJson('/api/v1/admin/profile', [
            'name' => 'Admin',
            'email' => 'products@test.com',
            'current_password' => 'wrong-password',
            'password' => 'brand-new-secret',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }
}
