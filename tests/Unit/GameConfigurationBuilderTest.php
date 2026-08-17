<?php

namespace Tests\Unit;

use App\Enums\GameType;
use App\Services\GameConfigurationBuilder;
use Tests\TestCase;

class GameConfigurationBuilderTest extends TestCase
{
    public function test_it_builds_standardized_config_with_defaults(): void
    {
        $builder = new GameConfigurationBuilder;
        $config = $builder->build(GameType::TAP);

        $this->assertEquals('tap', $config['interaction']['type']);
        $this->assertGreaterThanOrEqual(1, $config['attempts']);
        $this->assertGreaterThanOrEqual(0.1, $config['success_threshold']);
        $this->assertTrue($config['allow_retry']);
    }

    public function test_it_preserves_custom_parameters(): void
    {
        $builder = new GameConfigurationBuilder;
        $config = $builder->build(GameType::CHOOSE, [
            'attempts' => 15,
            'success_threshold' => 0.9,
            'reward' => ['type' => 'gem', 'value' => 5],
        ]);

        $this->assertEquals(15, $config['attempts']);
        $this->assertEquals(0.9, $config['success_threshold']);
        $this->assertEquals('gem', $config['reward']['type']);
        $this->assertEquals(5, $config['reward']['value']);
    }
}
