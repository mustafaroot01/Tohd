<?php

namespace Database\Factories;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+964770'.fake()->unique()->numerify('#######'),
            'phone_verified_at' => now(),
            'address' => fake()->address(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => SubscriberStatus::ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the subscriber's phone has not been verified yet.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
            'status' => SubscriberStatus::UNVERIFIED,
        ]);
    }
}
