<?php

namespace Simulator\Tests;

use PHPUnit\Framework\TestCase;
use Simulator\Config;
use Simulator\Controller;
use Simulator\Readings;

class ControllerTest extends TestCase
{
    private const START = 1_000_000;

    /** A controller that has already finished its power-up wait. */
    private function controller(?Config $config = null): Controller
    {
        return new Controller($config ?? new Config, self::START - 3600);
    }

    private function readings(?float $roof = 50, ?float $underground = 80, bool $float = true): Readings
    {
        return new Readings($roof, $underground, $float);
    }

    public function test_starts_the_pump_when_the_roof_tank_is_low(): void
    {
        $c = $this->controller();

        $this->assertFalse($c->tick(self::START, $this->readings(roof: 30)));
        $this->assertTrue($c->tick(self::START, $this->readings(roof: 25)));
        $this->assertSame('roof tank low', $c->reason);
    }

    public function test_keeps_filling_until_the_roof_tank_is_full(): void
    {
        $c = $this->controller();
        $c->tick(self::START, $this->readings(roof: 20));

        $this->assertTrue($c->tick(self::START + 60, $this->readings(roof: 60)));
        $this->assertTrue($c->tick(self::START + 120, $this->readings(roof: 94.9)));
        $this->assertFalse($c->tick(self::START + 180, $this->readings(roof: 95)));
        $this->assertSame('roof tank full', $c->reason);

        // and it stays off as the level drops back through the middle
        $this->assertFalse($c->tick(self::START + 240, $this->readings(roof: 60)));
    }

    public function test_never_runs_while_the_underground_tank_is_low(): void
    {
        $c = $this->controller();

        $this->assertFalse($c->tick(self::START, $this->readings(roof: 10, underground: 14)));
        $this->assertTrue($c->dryBlocked);
        $this->assertSame('dry-run guard on', $c->reason);
    }

    public function test_waits_for_the_underground_tank_to_recover_before_running_again(): void
    {
        $c = $this->controller();
        $c->tick(self::START, $this->readings(roof: 10, underground: 10));

        $this->assertFalse($c->tick(self::START + 60, $this->readings(roof: 10, underground: 20)));
        $this->assertTrue($c->tick(self::START + 120, $this->readings(roof: 10, underground: 25)));
    }

    public function test_the_float_switch_stops_the_pump_even_if_the_level_sensor_looks_fine(): void
    {
        $c = $this->controller();
        $c->tick(self::START, $this->readings(roof: 10));

        $this->assertFalse($c->tick(self::START + 60, $this->readings(roof: 30, underground: 70, float: false)));
        $this->assertTrue($c->dryBlocked);
    }

    public function test_stops_if_a_sensor_goes_quiet(): void
    {
        $c = $this->controller();
        $c->tick(self::START, $this->readings(roof: 10));

        $this->assertFalse($c->tick(self::START + 10, $this->readings(roof: null)));
        $this->assertSame('roof sensor not reading', $c->reason);

        $this->assertFalse($c->tick(self::START + 20, $this->readings(roof: 10, underground: null)));
        $this->assertTrue($c->dryBlocked);
    }

    public function test_stops_and_latches_a_fault_after_running_too_long(): void
    {
        $c = $this->controller(new Config(maxRunMinutes: 30));
        $c->tick(self::START, $this->readings(roof: 20));

        $this->assertTrue($c->tick(self::START + 29 * 60, $this->readings(roof: 20)));
        $this->assertFalse($c->tick(self::START + 30 * 60, $this->readings(roof: 20)));
        $this->assertSame('max_run', $c->fault);

        // stays off until someone resets it, even in manual
        $c->command('on', self::START + 31 * 60);
        $this->assertFalse($c->tick(self::START + 31 * 60, $this->readings(roof: 20)));

        $c->command('reset', self::START + 32 * 60);
        $c->command('auto', self::START + 32 * 60);
        $this->assertTrue($c->tick(self::START + 32 * 60, $this->readings(roof: 20)));
        $this->assertNull($c->fault);
    }

    public function test_a_fault_survives_a_reboot(): void
    {
        $c = $this->controller();
        $c->fault = 'max_run';

        $c->boot(self::START);

        $this->assertSame('max_run', $c->fault);
    }

    public function test_waits_after_power_comes_back(): void
    {
        $c = new Controller(new Config(powerWaitSeconds: 120), self::START);

        $this->assertFalse($c->tick(self::START + 119, $this->readings(roof: 10)));
        $this->assertSame('power-up wait', $c->reason);
        $this->assertTrue($c->tick(self::START + 120, $this->readings(roof: 10)));
    }

    public function test_manual_on_and_off_override_the_thresholds(): void
    {
        $c = $this->controller();

        $c->command('on', self::START);
        $this->assertTrue($c->tick(self::START, $this->readings(roof: 60)));
        $this->assertSame('on in manual mode', $c->reason);

        $c->command('off', self::START);
        $this->assertFalse($c->tick(self::START, $this->readings(roof: 10)));
        $this->assertSame('off in manual mode', $c->reason);
    }

    public function test_manual_on_still_stops_when_the_roof_tank_is_full(): void
    {
        $c = $this->controller();
        $c->command('on', self::START);
        $c->tick(self::START, $this->readings(roof: 80));

        $this->assertFalse($c->tick(self::START + 60, $this->readings(roof: 95)));
        $this->assertFalse($c->tick(self::START + 120, $this->readings(roof: 90)));
    }

    public function test_manual_on_still_respects_the_underground_tank(): void
    {
        $c = $this->controller();
        $c->command('on', self::START);

        $this->assertFalse($c->tick(self::START, $this->readings(roof: 50, underground: 5)));
    }

    public function test_manual_mode_goes_back_to_auto_after_the_timeout(): void
    {
        $c = $this->controller(new Config(manualTimeoutMinutes: 120));
        $c->command('off', self::START);

        $c->tick(self::START + 119 * 60, $this->readings(roof: 10));
        $this->assertSame('manual', $c->mode);

        $this->assertTrue($c->tick(self::START + 120 * 60, $this->readings(roof: 10)));
        $this->assertSame('auto', $c->mode);
    }

    public function test_switching_back_to_auto_follows_the_thresholds_again(): void
    {
        $c = $this->controller();
        $c->command('off', self::START);
        $c->tick(self::START, $this->readings(roof: 10));

        $c->command('auto', self::START + 10);

        $this->assertTrue($c->tick(self::START + 10, $this->readings(roof: 10)));
    }
}
