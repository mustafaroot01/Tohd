<?php

namespace Tests;

use App\Contracts\SmsGatewayInterface;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakeSmsGateway;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(SmsGatewayInterface::class, FakeSmsGateway::class);
    }

    protected function fakeSmsGateway(): FakeSmsGateway
    {
        return $this->app->make(SmsGatewayInterface::class);
    }
}
