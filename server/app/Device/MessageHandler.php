<?php

namespace App\Device;

use App\Events\DeviceUpdated;
use App\Models\PumpRun;
use App\Models\Reading;
use Illuminate\Support\Facades\Log;

/**
 * Handles every message the controller sends. The controller only reports its state;
 * pump runs and notifications come from comparing each report to the last.
 */
class MessageHandler
{
    public function __construct(private DeviceState $state, private Ntfy $ntfy) {}

    public function handle(string $topic, string $message): void
    {
        match ($topic) {
            Device::topic('status') => $this->status($message),
            Device::topic('state') => $this->state($message),
            Device::topic('sim/state') => $this->simulator($message),
            default => null,
        };
    }

    private function status(string $message): void
    {
        if ($message === 'online') {
            $this->state->update(['online' => true, 'last_seen' => now()->getTimestamp()]);
            $this->broadcast();
        } else {
            $this->controllerWentOffline();
        }
    }

    private function state(string $message): void
    {
        $data = json_decode($message, true);

        if (! is_array($data) || ! array_key_exists('pump', $data)) {
            return;
        }

        $values = array_intersect_key($data, DeviceState::EMPTY);
        $before = $this->state->get();
        $after = $this->state->update([
            ...$values,
            'online' => true,
            'last_seen' => now()->getTimestamp(),
            'offline_notified' => false,
        ]);

        $this->trackRun($after);
        $this->saveReading($after);
        $this->notify($before, $after);
        $this->broadcast();
    }

    private function simulator(string $message): void
    {
        $data = json_decode($message, true);

        if (is_array($data)) {
            $this->state->updateSimulator($data);
            $this->broadcast();
        }
    }

    private function trackRun(array $state): void
    {
        $open = PumpRun::open();

        if ($state['pump'] && ! $open) {
            PumpRun::create([
                'started_at' => now(),
                'start_reason' => $state['reason'],
                'mode' => $state['mode'],
            ]);
        } elseif (! $state['pump'] && $open) {
            $open->finish($state['reason']);
        }
    }

    private function saveReading(array $state): void
    {
        $last = Reading::query()->latest('recorded_at')->value('recorded_at');

        if ($last && $last->diffInSeconds(now()) < config('device.reading_interval_seconds')) {
            return;
        }

        Reading::create([
            'roof' => $state['roof'],
            'underground' => $state['underground'],
            'pump' => $state['pump'],
            'recorded_at' => now(),
        ]);
    }

    private function notify(array $before, array $after): void
    {
        if ($after['fault'] === 'max_run' && $before['fault'] !== 'max_run') {
            $minutes = app(Settings::class)->all()['max_run_minutes'];
            $this->ntfy->send(
                'Safety stop',
                "The pump hit the {$minutes}-minute run limit without filling the roof tank. Check the pipes and sensors, then reset it from the page.",
                'high',
                ['warning'],
            );
        }

        if ($after['dry_blocked'] && ! $before['dry_blocked']) {
            $this->ntfy->send(
                'Dry-run guard on',
                'The underground tank is low or its sensor isn\'t reading, so the pump stays off until the tank is back at the resume level.',
                'default',
                ['droplet'],
            );
        }

        if ($after['roof'] === null && $before['roof'] !== null) {
            $this->ntfy->send(
                'Roof sensor stopped reading',
                'The pump stays off until the roof tank sensor is working again.',
                'high',
                ['warning'],
            );
        }
    }

    /** The controller went quiet, so whatever the pump was doing has ended. */
    public function controllerWentOffline(): void
    {
        $this->state->update(['online' => false]);
        PumpRun::open()?->finish('controller went offline');
        $this->broadcast();
    }

    /** Tells open pages to refresh. A broadcast that fails shouldn't stop the controller's message being handled. */
    private function broadcast(): void
    {
        try {
            broadcast(new DeviceUpdated);
        } catch (\Throwable $e) {
            Log::warning('broadcast failed: '.$e->getMessage());
        }
    }
}
