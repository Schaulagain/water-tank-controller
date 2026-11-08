<?php

namespace Simulator;

/**
 * A pretend house: an underground tank, a roof tank, a pump between them,
 * people using water from the roof tank, and the town supply that tops up
 * the underground tank when it's running.
 *
 * $speed makes the water move faster than real life so a demo doesn't take
 * half an hour. Timers on the controller still use real time.
 */
final class World
{
    public float $roof = 60;          // percent, the real level

    public float $underground = 70;   // percent, the real level

    public string $usage = 'normal';  // none, normal or heavy

    public bool $supply = false;      // town water flowing into the underground tank

    public bool $power = true;

    public bool $roofSensorStuck = false;

    public bool $pump = false;

    private ?float $stuckRoofReading = null;

    private const ROOF_LITRES = 1000;

    private const UNDERGROUND_LITRES = 3000;

    private const PUMP_LITRES_PER_MIN = 40;

    private const SUPPLY_LITRES_PER_MIN = 20;

    private const USAGE_LITRES_PER_MIN = ['none' => 0, 'normal' => 5, 'heavy' => 25];

    public function __construct(public float $speed = 1, private ?\Closure $noise = null)
    {
        $this->noise ??= fn () => mt_rand(-50, 50) / 100;
    }

    public function step(float $seconds): void
    {
        $minutes = $seconds / 60 * $this->speed;

        $used = self::USAGE_LITRES_PER_MIN[$this->usage] * $minutes;
        $this->roof = $this->clamp($this->roof - $used / self::ROOF_LITRES * 100);

        if ($this->supply) {
            $in = self::SUPPLY_LITRES_PER_MIN * $minutes;
            $this->underground = $this->clamp($this->underground + $in / self::UNDERGROUND_LITRES * 100);
        }

        if ($this->pump && $this->power) {
            $available = $this->underground / 100 * self::UNDERGROUND_LITRES;
            $moved = min(self::PUMP_LITRES_PER_MIN * $minutes, $available);
            $this->underground = $this->clamp($this->underground - $moved / self::UNDERGROUND_LITRES * 100);
            $this->roof = $this->clamp($this->roof + $moved / self::ROOF_LITRES * 100);
        }
    }

    public function readings(): Readings
    {
        if ($this->roofSensorStuck) {
            $this->stuckRoofReading ??= $this->roof;
        } else {
            $this->stuckRoofReading = null;
        }

        $roof = $this->stuckRoofReading ?? $this->roof + ($this->noise)();

        return new Readings(
            roof: round($this->clamp($roof), 1),
            underground: round($this->clamp($this->underground + ($this->noise)()), 1),
            undergroundFloat: $this->underground >= 5,
        );
    }

    public function control(string $action, mixed $value): void
    {
        match ($action) {
            'usage' => $this->usage = in_array($value, ['none', 'normal', 'heavy'], true) ? $value : $this->usage,
            'supply' => $this->supply = (bool) $value,
            'power' => $this->power = (bool) $value,
            'roof_sensor_stuck' => $this->roofSensorStuck = (bool) $value,
            'roof_level' => $this->roof = $this->clamp((float) $value),
            'underground_level' => $this->underground = $this->clamp((float) $value),
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'roof' => round($this->roof, 1),
            'underground' => round($this->underground, 1),
            'usage' => $this->usage,
            'supply' => $this->supply,
            'power' => $this->power,
            'roof_sensor_stuck' => $this->roofSensorStuck,
            'speed' => $this->speed,
        ];
    }

    private function clamp(float $value): float
    {
        return max(0, min(100, $value));
    }
}
