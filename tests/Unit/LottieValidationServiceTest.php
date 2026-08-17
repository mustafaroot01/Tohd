<?php

namespace Tests\Unit;

use App\Exceptions\InvalidLottieFileException;
use App\Services\LottieValidationService;
use Tests\TestCase;

class LottieValidationServiceTest extends TestCase
{
    protected LottieValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LottieValidationService;
    }

    public function test_it_validates_valid_lottie_json_content(): void
    {
        $validLottie = json_encode([
            'v' => '5.7.4',
            'fr' => 60,
            'ip' => 0,
            'op' => 180,
            'w' => 500,
            'h' => 500,
            'layers' => [
                ['ty' => 4, 'nm' => 'StarLayer'],
            ],
        ]);

        $result = $this->service->validateAndExtract($validLottie);

        $this->assertIsArray($result);
        $this->assertEquals(3.0, $result['metadata']['duration_seconds']);
        $this->assertEquals(180, $result['metadata']['total_frames']);
        $this->assertEquals(60.0, $result['metadata']['frame_rate']);
        $this->assertNotEmpty($result['checksum']);
    }

    public function test_it_rejects_corrupted_json(): void
    {
        $this->expectException(InvalidLottieFileException::class);
        $this->service->validateAndExtract('{not_a_valid_json');
    }

    public function test_it_rejects_json_missing_required_lottie_keys(): void
    {
        $this->expectException(InvalidLottieFileException::class);
        $this->service->validateAndExtract(json_encode([
            'title' => 'Missing required keys',
        ]));
    }
}
