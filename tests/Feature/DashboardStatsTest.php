<?php

namespace Tests\Feature;

use App\Enums\CurriculumStatus;
use App\Enums\ProductStatus;
use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_correct_subscriber_and_revenue_figures(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        Subscriber::create(['name' => 'A', 'phone' => '+9647701230040', 'password' => bcrypt('x'), 'status' => 'ACTIVE']);
        Subscriber::create(['name' => 'B', 'phone' => '+9647701230041', 'password' => bcrypt('x'), 'status' => 'UNVERIFIED']);
        Subscriber::create(['name' => 'C', 'phone' => '+9647701230042', 'password' => bcrypt('x'), 'status' => 'SUSPENDED']);

        $curriculum = Curriculum::create([
            'code' => 'CURR-DASH',
            'name' => 'منهج',
            'slug' => 'curr-dash',
            'status' => CurriculumStatus::PUBLISHED,
        ]);

        $product = Product::create([
            'code' => 'PROD-DASH',
            'name' => 'منتج',
            'slug' => 'prod-dash',
            'curriculum_id' => $curriculum->id,
            'duration_days' => 30,
            'status' => ProductStatus::ACTIVE,
            'price' => 10000,
        ]);

        // Two activated codes (should count toward revenue) + one still available (should not).
        ActivationCode::create([
            'code' => 'DASH-0001',
            'product_id' => $product->id,
            'status' => 'ACTIVATED',
            'activated_at' => now(),
        ]);
        ActivationCode::create([
            'code' => 'DASH-0002',
            'product_id' => $product->id,
            'status' => 'ACTIVATED',
            'activated_at' => now(),
        ]);
        ActivationCode::create([
            'code' => 'DASH-0003',
            'product_id' => $product->id,
            'status' => 'AVAILABLE',
        ]);

        $adminToken = $admin->createToken('admin')->plainTextToken;
        $res = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/admin/dashboard');

        $res->assertStatus(200)
            ->assertJsonPath('data.subscribers_total', 3)
            ->assertJsonPath('data.subscribers_active', 1)
            ->assertJsonPath('data.subscribers_unverified', 1)
            ->assertJsonPath('data.subscribers_suspended', 1)
            ->assertJsonPath('data.active_activations_count', 2)
            ->assertJsonPath('data.available_activations_count', 1)
            ->assertJsonPath('data.total_revenue', 20000)
            ->assertJsonPath('data.today_revenue', 20000)
            ->assertJsonPath('data.month_revenue', 20000);
    }
}
