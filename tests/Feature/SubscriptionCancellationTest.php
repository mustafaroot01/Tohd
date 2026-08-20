<?php

namespace Tests\Feature;

use App\Enums\CurriculumStatus;
use App\Enums\ProductStatus;
use App\Models\ActivationCode;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\UserCurriculumAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_active_subscription_and_serial_stays_used(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230020',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $curriculum = Curriculum::create([
            'code' => 'CURR-CANCEL',
            'name' => 'منهج الإلغاء',
            'slug' => 'curr-cancel',
            'status' => CurriculumStatus::PUBLISHED,
        ]);

        $product = Product::create([
            'code' => 'PROD-CANCEL',
            'name' => 'منتج الإلغاء',
            'slug' => 'prod-cancel',
            'curriculum_id' => $curriculum->id,
            'duration_days' => 30,
            'status' => ProductStatus::ACTIVE,
            'price' => 50,
        ]);

        $activation = ActivationCode::create([
            'code' => 'CANCEL-0001',
            'product_id' => $product->id,
            'status' => 'ACTIVATED',
            'activated_by' => $subscriber->id,
            'activated_at' => now(),
        ]);

        $assignment = UserCurriculumAssignment::create([
            'subscriber_id' => $subscriber->id,
            'curriculum_id' => $curriculum->id,
            'activation_id' => $activation->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'status' => 'ACTIVE',
        ]);

        $adminToken = $admin->createToken('admin')->plainTextToken;

        $cancelRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/subscribers/{$subscriber->id}/assignments/{$assignment->id}/cancel", [
                'reason' => 'طلب ولي الأمر',
            ]);

        $cancelRes->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED')
            ->assertJsonPath('data.cancellation_reason', 'طلب ولي الأمر');

        $this->assertDatabaseHas('user_curriculum_assignments', [
            'id' => $assignment->id,
            'status' => 'CANCELLED',
        ]);

        // The serial remains permanently ACTIVATED, never returns to AVAILABLE.
        $this->assertDatabaseHas('activation_codes', [
            'id' => $activation->id,
            'status' => 'ACTIVATED',
        ]);
    }

    public function test_cannot_cancel_an_already_cancelled_assignment(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $subscriber = Subscriber::create([
            'name' => 'Faris',
            'phone' => '+9647701230021',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $curriculum = Curriculum::create([
            'code' => 'CURR-CANCEL-2',
            'name' => 'منهج 2',
            'slug' => 'curr-cancel-2',
            'status' => CurriculumStatus::PUBLISHED,
        ]);

        $assignment = UserCurriculumAssignment::create([
            'subscriber_id' => $subscriber->id,
            'curriculum_id' => $curriculum->id,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(10),
            'status' => 'CANCELLED',
            'cancelled_at' => now()->subDay(),
        ]);

        $adminToken = $admin->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/admin/subscribers/{$subscriber->id}/assignments/{$assignment->id}/cancel");

        $response->assertStatus(409)->assertJsonPath('error_code', 'ASSIGNMENT_NOT_ACTIVE');
    }
}
