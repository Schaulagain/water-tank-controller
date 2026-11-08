<?php

namespace Simulator;

/**
 * Decides whether the pump should run. This is the logic the ESP32 firmware
 * will follow too, so it's kept free of MQTT and timing details: give it the
 * time and the sensor readings, and it says on or off.
 *
 * The rules are written up in docs/control-logic.md.
 */
final class Controller
{
    public string $mode = 'auto';

    public bool $manualPump = false;

    public ?int $manualUntil = null;

    public bool $pump = false;

    public ?int $pumpSince = null;

    public ?string $fault = null;

    public bool $dryBlocked = false;

    public int $powerWaitUntil = 0;

    public string $reason = 'starting up';

    public function __construct(public Config $config, int $now)
    {
        $this->boot($now);
    }

    /**
     * Called when the device powers up. Everything resets except a fault,
     * which the real board keeps in flash so a reboot can't clear it.
     */
    public function boot(int $now): void
    {
        $this->mode = 'auto';
        $this->manualPump = false;
        $this->manualUntil = null;
        $this->pump = false;
        $this->pumpSince = null;
        $this->dryBlocked = false;
        $this->powerWaitUntil = $now + $this->config->powerWaitSeconds;
        $this->reason = 'waiting after power came back';
    }

    public function command(string $action, int $now): void
    {
        match ($action) {
            'auto' => [$this->mode, $this->manualUntil] = ['auto', null],
            'on', 'off' => [$this->mode, $this->manualPump, $this->manualUntil] = [
                'manual',
                $action === 'on',
                $now + $this->config->manualTimeoutMinutes * 60,
            ],
            'reset' => $this->fault = null,
            default => null,
        };
    }

    public function tick(int $now, Readings $readings): bool
    {
        if ($this->mode === 'manual' && $now >= $this->manualUntil) {
            $this->mode = 'auto';
            $this->manualUntil = null;
        }

        $this->updateDryBlock($readings);

        [$want, $reason] = $this->decide($now, $readings);

        if ($want && $this->pump && $now - $this->pumpSince >= $this->config->maxRunMinutes * 60) {
            $this->fault = 'max_run';
            [$want, $reason] = [false, 'ran too long, stopped for safety'];
        }

        if ($want && ! $this->pump) {
            $this->pumpSince = $now;
        } elseif (! $want) {
            $this->pumpSince = null;
        }

        $this->pump = $want;
        $this->reason = $reason;

        return $want;
    }

    /**
     * Blocks the pump while the underground tank is low, and only lets it run
     * again once the level is back above a higher mark. The float switch near
     * the bottom of the tank blocks it too, in case the level sensor is wrong.
     */
    private function updateDryBlock(Readings $readings): void
    {
        $level = $readings->underground;

        if ($level === null || ! $readings->undergroundFloat || $level < $this->config->undergroundBlock) {
            $this->dryBlocked = true;
        } elseif ($this->dryBlocked && $level >= $this->config->undergroundResume) {
            $this->dryBlocked = false;
        }
    }

    /** @return array{bool, string} */
    private function decide(int $now, Readings $readings): array
    {
        $roof = $readings->roof;

        if ($this->fault !== null) {
            return [false, 'stopped for safety, needs a reset'];
        }

        if ($now < $this->powerWaitUntil) {
            return [false, 'waiting after power came back'];
        }

        if ($this->dryBlocked) {
            return [false, 'underground tank too low'];
        }

        if ($roof === null) {
            return [false, 'roof sensor not reading'];
        }

        if ($this->mode === 'manual') {
            if ($this->manualPump && $roof >= $this->config->roofOff) {
                $this->manualPump = false;

                return [false, 'roof tank full'];
            }

            return $this->manualPump ? [true, 'turned on by hand'] : [false, 'turned off by hand'];
        }

        if ($this->pump) {
            return $roof < $this->config->roofOff ? [true, 'filling roof tank'] : [false, 'roof tank full'];
        }

        return $roof <= $this->config->roofOn ? [true, 'roof tank low'] : [false, 'roof tank has enough water'];
    }

    public function toArray(): array
    {
        return [
            'pump' => $this->pump,
            'pump_since' => $this->pumpSince,
            'mode' => $this->mode,
            'manual_until' => $this->manualUntil,
            'reason' => $this->reason,
            'fault' => $this->fault,
            'dry_blocked' => $this->dryBlocked,
            'power_wait_until' => $this->powerWaitUntil,
        ];
    }
}
