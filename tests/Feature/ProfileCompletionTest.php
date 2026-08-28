<?php

namespace Tests\Feature;

use App\Models\Governorate;
use App\Models\Subscriber;
use App\Models\SubscriberProfile;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The second registration step and the switch that hides it.
 *
 * Two behaviours matter and are pinned here: with the switch OFF the feature
 * leaves no trace anywhere in the API, and with it ON it is mandatory — the
 * training endpoints refuse until the details are filled.
 */
class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected Subscriber $subscriber;

    protected Governorate $governorate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = Subscriber::create([
            'name' => 'ولي الأمر الثلاثي',
            'phone' => '+9647701239999',
            'password' => Hash::make('original-password'),
            'status' => 'ACTIVE',
            'phone_verified_at' => now(),
        ]);

        $this->governorate = Governorate::create(['name' => 'بغداد', 'code' => 'BGD', 'sort_order' => 1]);

        $this->withHeader('Authorization', 'Bearer '.$this->subscriber->createToken('mobile')->plainTextToken);
    }

    private function enable(bool $on = true): void
    {
        SystemSetting::get()->update(['profile_completion_enabled' => $on]);
    }

    private function validDetails(array $overrides = []): array
    {
        return array_merge([
            'governorate_id' => $this->governorate->id,
            'gender' => 'MALE',
            'age' => 6,
            'family_order' => 2,
            'delivery_type' => 'NATURAL',
        ], $overrides);
    }

    // ── switched off: the feature does not exist ────────────────────────────

    public function test_while_switched_off_no_response_mentions_the_step(): void
    {
        $profile = $this->getJson('/api/v1/app/profile')->assertStatus(200);
        $profile->assertJsonMissingPath('data.profile_completion');
        $profile->assertJsonMissingPath('data.details');

        $this->getJson('/api/v1/app/home')
            ->assertStatus(200)
            ->assertJsonMissingPath('data.profile_completion');
    }

    public function test_while_switched_off_its_endpoints_answer_not_found(): void
    {
        $this->getJson('/api/v1/app/governorates')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->postJson('/api/v1/app/profile/details', $this->validDetails())
            ->assertStatus(404);

        $this->assertSame(0, SubscriberProfile::count());
    }

    public function test_while_switched_off_the_endpoints_look_like_they_were_never_built(): void
    {
        // no token at all: a prober must not be able to tell these URIs apart
        // from any path that does not exist — on any verb
        $this->flushHeaders();

        foreach (['/api/v1/app/governorates', '/api/v1/app/profile/details'] as $uri) {
            foreach (['OPTIONS', 'GET', 'POST', 'PUT', 'DELETE'] as $verb) {
                $response = $this->call($verb, $uri);

                $this->assertSame(404, $response->status(), "$verb $uri leaked (got {$response->status()})");
                $this->assertNull($response->headers->get('Allow'), "$verb $uri leaked an Allow header");
            }
        }

        // …exactly what an unregistered path answers
        $this->assertSame(404, $this->call('OPTIONS', '/api/v1/app/nothing-here')->status());
    }

    public function test_while_switched_off_training_is_not_blocked(): void
    {
        $this->getJson('/api/v1/app/progress')->assertStatus(200);
    }

    // ── switched on: the step is mandatory ──────────────────────────────────

    public function test_switching_it_on_tells_the_app_what_is_missing(): void
    {
        $this->enable();

        $this->getJson('/api/v1/app/home')
            ->assertStatus(200)
            ->assertJsonPath('data.profile_completion.is_complete', false)
            ->assertJsonPath('data.profile_completion.missing_fields', ['governorate_id', 'gender', 'age', 'family_order', 'delivery_type']);

        $this->getJson('/api/v1/app/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.profile_completion.is_complete', false)
            ->assertJsonPath('data.details', null);
    }

    public function test_training_is_refused_until_the_details_are_filled(): void
    {
        $this->enable();

        $this->getJson('/api/v1/app/progress')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'PROFILE_INCOMPLETE');

        // …but the screens the app needs to fix it stay reachable
        $this->getJson('/api/v1/app/home')->assertStatus(200);
        $this->getJson('/api/v1/app/profile')->assertStatus(200);
        $this->getJson('/api/v1/app/governorates')->assertStatus(200);
    }

    public function test_the_governorate_list_offers_active_ones_in_order(): void
    {
        $this->enable();
        Governorate::create(['name' => 'البصرة', 'code' => 'BSR', 'sort_order' => 3]);
        Governorate::create(['name' => 'مخفية', 'code' => 'HID', 'sort_order' => 2, 'is_active' => false]);

        $names = $this->getJson('/api/v1/app/governorates')->assertStatus(200)->json('data.*.name');

        $this->assertSame(['بغداد', 'البصرة'], $names);
    }

    public function test_saving_the_details_completes_the_profile_and_unlocks_training(): void
    {
        $this->enable();

        $this->postJson('/api/v1/app/profile/details', $this->validDetails())
            ->assertStatus(200)
            ->assertJsonPath('data.profile_completion.is_complete', true)
            ->assertJsonPath('data.profile_completion.missing_fields', [])
            ->assertJsonPath('data.details.age', 6)
            ->assertJsonPath('data.details.family_order', 2)
            ->assertJsonPath('data.details.gender', 'MALE')
            ->assertJsonPath('data.details.gender_label', 'ذكر')
            ->assertJsonPath('data.details.delivery_type_label', 'طبيعية')
            ->assertJsonPath('data.details.governorate_name', 'بغداد');

        $this->getJson('/api/v1/app/progress')->assertStatus(200);

        $this->assertSame(1, SubscriberProfile::count());
        $this->assertNotNull(SubscriberProfile::first()->completed_at);
    }

    public function test_answers_can_be_corrected_without_creating_a_second_row(): void
    {
        $this->enable();

        $this->postJson('/api/v1/app/profile/details', $this->validDetails())->assertStatus(200);
        $this->postJson('/api/v1/app/profile/details', $this->validDetails(['age' => 7, 'delivery_type' => 'CESAREAN']))
            ->assertStatus(200)
            ->assertJsonPath('data.details.age', 7)
            ->assertJsonPath('data.details.delivery_type', 'CESAREAN');

        $this->assertSame(1, SubscriberProfile::count());
    }

    public function test_every_field_is_required_and_a_hidden_governorate_is_refused(): void
    {
        $this->enable();

        $this->postJson('/api/v1/app/profile/details', ['gender' => 'MALE'])
            ->assertStatus(422)
            ->assertJsonPath('errors.governorate_id.0', 'المحافظة مطلوبة')
            ->assertJsonPath('errors.age.0', 'العمر مطلوب');

        $hidden = Governorate::create(['name' => 'مخفية', 'code' => 'HID', 'is_active' => false]);

        $this->postJson('/api/v1/app/profile/details', $this->validDetails(['governorate_id' => $hidden->id]))
            ->assertStatus(422)
            ->assertJsonPath('errors.governorate_id.0', 'المحافظة المختارة غير متاحة');

        $this->postJson('/api/v1/app/profile/details', $this->validDetails(['age' => 40]))
            ->assertStatus(422)
            ->assertJsonPath('errors.age.0', 'العمر يجب ألّا يتجاوز 18 سنة');

        $this->assertSame(0, SubscriberProfile::count());
    }

    public function test_switching_it_back_off_hides_everything_again_without_losing_the_answers(): void
    {
        $this->enable();
        $this->postJson('/api/v1/app/profile/details', $this->validDetails())->assertStatus(200);

        $this->enable(false);

        $this->getJson('/api/v1/app/profile')->assertStatus(200)->assertJsonMissingPath('data.details');
        $this->getJson('/api/v1/app/home')->assertStatus(200)->assertJsonMissingPath('data.profile_completion');
        $this->getJson('/api/v1/app/governorates')->assertStatus(404);

        // the row is still there, waiting for the switch to come back on
        $this->assertSame(1, SubscriberProfile::count());
    }
}
