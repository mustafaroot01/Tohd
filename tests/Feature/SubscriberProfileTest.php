<?php

namespace Tests\Feature;

use App\Enums\SubscriberStatus;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * App\ProfileController — the subscriber-facing profile. Previously untested,
 * and it used to let a stolen token change the password with no proof of the old
 * one, which is a permanent account takeover.
 */
class SubscriberProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Subscriber $subscriber;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = Subscriber::create([
            'name' => 'فارس',
            'phone' => '+9647701239999',
            'password' => Hash::make('original-password'),
            'status' => SubscriberStatus::ACTIVE,
            'phone_verified_at' => now(),
        ]);

        $this->token = $this->subscriber->createToken('mobile')->plainTextToken;
    }

    private function asSubscriber(): self
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token);

        return $this;
    }

    public function test_subscriber_can_read_own_profile(): void
    {
        $this->asSubscriber()->getJson('/api/v1/app/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.phone', '+9647701239999')
            ->assertJsonMissingPath('data.password');
    }

    public function test_subscriber_can_edit_name_without_touching_the_password(): void
    {
        $this->asSubscriber()->putJson('/api/v1/app/profile', ['name' => 'فارس البطل'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'فارس البطل');

        $this->assertTrue(Hash::check('original-password', $this->subscriber->fresh()->password));
    }

    public function test_password_change_without_the_current_password_is_refused(): void
    {
        $res = $this->asSubscriber()->putJson('/api/v1/app/profile', [
            'password' => 'attacker-chosen',
            'password_confirmation' => 'attacker-chosen',
        ]);

        $res->assertStatus(422);
        $this->assertArrayHasKey('current_password', $res->json('errors'));
        $this->assertTrue(Hash::check('original-password', $this->subscriber->fresh()->password));
    }

    public function test_password_change_with_a_wrong_current_password_is_refused(): void
    {
        $this->asSubscriber()->putJson('/api/v1/app/profile', [
            'current_password' => 'not-the-password',
            'password' => 'attacker-chosen',
            'password_confirmation' => 'attacker-chosen',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_CURRENT_PASSWORD');

        $this->assertTrue(Hash::check('original-password', $this->subscriber->fresh()->password));
    }

    public function test_password_change_succeeds_with_the_correct_current_password(): void
    {
        $this->asSubscriber()->putJson('/api/v1/app/profile', [
            'current_password' => 'original-password',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('a-brand-new-password', $this->subscriber->fresh()->password));
    }

    public function test_changing_the_phone_forces_reverification(): void
    {
        $this->asSubscriber()->putJson('/api/v1/app/profile', ['phone' => '07701238888'])
            ->assertStatus(200);

        $fresh = $this->subscriber->fresh();

        $this->assertSame('+9647701238888', $fresh->phone);
        $this->assertNull($fresh->phone_verified_at);
        $this->assertSame(SubscriberStatus::UNVERIFIED, $fresh->status);
        $this->assertNotEmpty($this->fakeSmsGateway()->sent);
    }
}
