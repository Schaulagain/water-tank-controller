<?php

namespace Tests;

use App\Device\Publisher;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakePublisher;

abstract class TestCase extends BaseTestCase
{
    protected FakePublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->publisher = new FakePublisher;
        $this->app->instance(Publisher::class, $this->publisher);
        config(['device.mqtt.topic' => 'home/water', 'device.ntfy.topic' => null]);
    }
}
