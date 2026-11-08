<?php

namespace Simulator\Tests;

use PHPUnit\Framework\TestCase;
use Simulator\World;

class WorldTest extends TestCase
{
    private function world(): World
    {
        return new World(speed: 1, noise: fn () => 0.0);
    }

    public function test_the_pump_moves_water_from_underground_to_the_roof(): void
    {
        $w = $this->world();
        $w->usage = 'none';
        [$w->roof, $w->underground, $w->pump] = [50, 50, true];

        $w->step(60); // 40 litres

        $this->assertEqualsWithDelta(54, $w->roof, 0.01);
        $this->assertEqualsWithDelta(48.67, $w->underground, 0.01);
    }

    public function test_the_pump_does_nothing_without_power_or_water(): void
    {
        $w = $this->world();
        $w->usage = 'none';
        [$w->roof, $w->underground, $w->pump, $w->power] = [50, 50, true, false];
        $w->step(60);
        $this->assertSame(50.0, (float) $w->roof);

        [$w->power, $w->underground] = [true, 0];
        $w->step(60);
        $this->assertSame(50.0, (float) $w->roof);
    }

    public function test_a_stuck_sensor_keeps_reporting_the_same_level(): void
    {
        $w = $this->world();
        $w->roof = 40;
        $w->control('roof_sensor_stuck', true);
        $w->readings();

        $w->roof = 80;

        $this->assertSame(40.0, $w->readings()->roof);
    }

    public function test_the_float_switch_drops_when_the_underground_tank_is_nearly_empty(): void
    {
        $w = $this->world();
        $w->underground = 4;

        $this->assertFalse($w->readings()->undergroundFloat);
    }
}
