<?php

namespace App\Device;

use Illuminate\Support\Facades\Cache;

/**
 * The latest thing the controller told us, kept in the cache so the page can
 * show it without asking the controller.
 */
class DeviceState
{
    private const KEY = 'device.state';

    private const SIMULATOR_KEY = 'device.sim';

    public const EMPTY = [
        'online' => false,
        'last_seen' => null,
        'offline_notified' => false,
        'roof' => null,
        'underground' => null,
        'underground_float' => null,
        'pump' => false,
        'pump_since' => null,
        'mode' => 'auto',
        'manual_pump' => null,
        'manual_until' => null,
        'reason' => null,
        'fault' => null,
        'dry_blocked' => false,
        'power_wait_until' => null,
    ];

    public function get(): array
    {
        return array_merge(self::EMPTY, Cache::get(self::KEY, []));
    }

    public function update(array $values): array
    {
        $state = array_merge($this->get(), $values);
        Cache::forever(self::KEY, $state);

        return $state;
    }

    /** The controller counts as online if it said so and we've heard from it recently. */
    public function isOnline(?array $state = null): bool
    {
        $state ??= $this->get();

        return $state['online']
            && $state['last_seen'] !== null
            && $state['last_seen'] >= now()->getTimestamp() - config('device.offline_after_seconds');
    }

    /** What the simulator says about its pretend tanks, for the simulator page. */
    public function simulator(): array
    {
        return Cache::get(self::SIMULATOR_KEY, []);
    }

    public function updateSimulator(array $values): void
    {
        Cache::forever(self::SIMULATOR_KEY, $values);
    }
}
