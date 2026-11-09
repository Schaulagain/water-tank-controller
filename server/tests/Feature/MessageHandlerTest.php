<?php

namespace Tests\Feature;

use App\Device\DeviceState;
use App\Device\MessageHandler;
use App\Models\PumpRun;
use App\Models\Reading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessageHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $state): void
    {
        app(MessageHandler::class)->handle('home/water/state', json_encode([
            'roof' => 50, 'underground' => 70, 'underground_float' => true, 'pump' => false,
            'pump_since' => null, 'mode' => 'auto', 'manual_until' => null, 'reason' => 'roof tank has enough water',
            'fault' => null, 'dry_blocked' => false, 'power_wait_until' => 0, 'time' => now()->getTimestamp(),
            ...$state,
        ]));
    }

    public function test_keeps_the_latest_state_and_marks_the_controller_online(): void
    {
        $this->report(['roof' => 63.5]);

        $state = app(DeviceState::class)->get();
        $this->assertSame(63.5, $state['roof']);
        $this->assertTrue(app(DeviceState::class)->isOnline());
    }

    public function test_saves_a_reading_at_most_every_30_seconds(): void
    {
        $this->report([]);
        $this->travel(10)->seconds();
        $this->report([]);
        $this->assertSame(1, Reading::count());

        $this->travel(25)->seconds();
        $this->report([]);
        $this->assertSame(2, Reading::count());
    }

    public function test_records_a_pump_run_from_start_to_finish(): void
    {
        $this->report(['pump' => true, 'reason' => 'roof tank low']);
        $this->travel(12)->minutes();
        $this->report(['pump' => true, 'reason' => 'filling roof tank']);
        $this->report(['pump' => false, 'reason' => 'roof tank full']);

        $run = PumpRun::sole();
        $this->assertSame('roof tank low', $run->start_reason);
        $this->assertSame('roof tank full', $run->stop_reason);
        $this->assertSame(12, $run->minutes());
    }

    public function test_going_offline_ends_a_running_pump_run(): void
    {
        $this->report(['pump' => true, 'reason' => 'roof tank low']);

        app(MessageHandler::class)->handle('home/water/status', 'offline');

        $this->assertSame('controller went offline', PumpRun::sole()->stop_reason);
        $this->assertFalse(app(DeviceState::class)->isOnline());
    }

    public function test_sends_a_notification_once_on_a_safety_stop(): void
    {
        config(['device.ntfy.topic' => 'my-tanks']);
        Http::fake();

        $this->report(['fault' => 'max_run', 'reason' => 'hit the run limit']);
        $this->report(['fault' => 'max_run']);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://ntfy.sh/my-tanks'
            && $request->header('Title')[0] === 'Safety stop'
            && $request->header('Priority')[0] === 'high');
    }

    public function test_tells_me_when_the_underground_tank_runs_low(): void
    {
        config(['device.ntfy.topic' => 'my-tanks']);
        Http::fake();

        $this->report([]);
        $this->report(['dry_blocked' => true]);

        Http::assertSent(fn ($request) => $request->header('Title')[0] === 'Dry-run guard on');
    }

    public function test_ignores_messages_it_cant_read(): void
    {
        app(MessageHandler::class)->handle('home/water/state', 'not json');
        app(MessageHandler::class)->handle('home/water/state', '{"roof": 10}');
        app(MessageHandler::class)->handle('someone/else/state', '{"pump": true}');

        $this->assertNull(app(DeviceState::class)->get()['last_seen']);
        $this->assertSame(0, Reading::count());
    }

    public function test_keeps_what_the_simulator_reports(): void
    {
        app(MessageHandler::class)->handle('home/water/sim/state', '{"roof": 40, "usage": "heavy"}');

        $this->assertSame('heavy', app(DeviceState::class)->simulator()['usage']);
    }
}
