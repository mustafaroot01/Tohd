<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppHomeAndProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_progress_and_home_apis(): void
    {
        $user = Subscriber::create([
            'name' => 'Faris User',
            'phone' => '+9647703333333',
            'password' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $token = $user->createToken('user')->plainTextToken;

        // 1. Home API
        $homeRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/app/home');

        $homeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['user', 'activation', 'assignment', 'today', 'progress', 'continue_session'],
            ]);

        // 2. Progress API
        $progRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/app/progress');

        $progRes->assertStatus(200)
            ->assertJsonPath('data.total_sessions', 0)
            ->assertJsonPath('data.average_accuracy', 0);

        // 3. Periodic Progress APIs (daily / weekly / monthly)
        $dailyRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/app/progress/daily');
        $dailyRes->assertStatus(200)->assertJsonPath('data.period', 'daily');

        $weeklyRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/app/progress/weekly');
        $weeklyRes->assertStatus(200)->assertJsonPath('data.period', 'weekly');

        $monthlyRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/app/progress/monthly');
        $monthlyRes->assertStatus(200)->assertJsonPath('data.period', 'monthly');
    }
}
