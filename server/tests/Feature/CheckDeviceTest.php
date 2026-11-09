<?php

namespace Tests\Feature;

use App\Device\DeviceState;
use App\Models\PumpRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['device.ntfy.topic' => 'my-tanks']);
        Http::fake();
        app(DeviceState::class)->update(['online' => true, 'last_seen' => now()->getTimestamp(), 'pump' => true]);
        PumpRun::create(['started_at' => now(), 'mode' => 'auto', 'start_reason' => 'roof tank low']);
    }

    public function test_leaves_a_controller_that_reported_recently_alone(): void
    {
        $this->travel(30)->seconds();
        $this->artisan('device:check');

        $this->assertTrue(app(DeviceState::class)->get()['online']);
    }

    public function test_marks_a_quiet_controller_offline_after_a_minute(): void
    {
        $this->travel(61)->seconds();
        $this->artisan('device:check');

        $this->assertFalse(app(DeviceState::class)->get()['online']);
        $this->assertSame('controller went offline', PumpRun::sole()->stop_reason);
        Http::assertNothingSent();
    }

    public function test_sends_one_notification_after_five_minutes(): void
    {
        $this->travel(5)->minutes();
        $this->artisan('device:check');
        $this->travel(1)->minutes();
        $this->artisan('device:check');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->header('Title')[0] === 'Water pump controller is offline');
    }
}
