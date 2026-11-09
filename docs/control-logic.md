# Control logic

What the controller does every second. It's implemented in [`simulator/src/Controller.php`](../simulator/src/Controller.php) and tested in [`simulator/tests/ControllerTest.php`](../simulator/tests/ControllerTest.php). The ESP32 firmware will follow the same rules, so this page is the spec for both.

The logic lives on the controller. The server only shows what's happening and sends commands, so the tanks keep filling properly when the WiFi or the server is down.

## Each tick

1. **Manual mode timeout.** In manual mode past `manual_until`, go back to auto mode.
2. **Dry-run guard.** Turn the guard on if the underground level is below the dry-run level (`underground_block`), the underground sensor isn't reading, or the float switch has dropped. Once on, it stays on until the level is back to the resume level (`underground_resume`).
3. **Decide**, taking the first rule that applies:

   | Situation | Pump | Reason shown |
   | --- | --- | --- |
   | Safety stop | off | safety stop, needs a reset |
   | Power-up wait not over (`power_wait_seconds`) | off | power-up wait |
   | Dry-run guard on | off | dry-run guard on |
   | Roof sensor not reading | off | roof sensor not reading |
   | Manual mode, on, roof at or above the stop level | off, and back to auto mode | roof tank full |
   | Manual mode | as set | on / off in manual mode |
   | Auto mode, pump running, roof below the stop level (`roof_off`) | on | filling roof tank |
   | Auto mode, pump running, roof at the stop level | off | roof tank full |
   | Auto mode, pump off, roof at or below the start level (`roof_on`) | on | roof tank low |
   | Auto mode, pump off | off | roof tank has enough water |

4. **Run limit.** If the pump has been on for the run limit (`max_run_minutes`), switch it off and trigger a safety stop (reported as `fault: "max_run"`). The tick that trips it reports the reason "hit the run limit", which is what the pump run's history shows, and later ticks report "safety stop, needs a reset". That covers a jammed sensor, a burst pipe or anything else that stops the roof tank filling.

## Power cuts

When the power goes, the controller goes with it. On power-up it starts in auto mode with the pump off and holds a power-up wait (`power_wait_seconds`) before running, because the supply often flickers before it settles. The dry-run guard starts on, so the pump only runs once the underground tank is at or above the resume level.

A safety stop is the one thing that survives a restart. The ESP32 keeps it in flash, so cycling the power can't hide a problem.

## Commands

- `on` / `off`: manual mode, until `manual_timeout_minutes` passes or `auto` is sent.
- `auto`: back to auto mode.
- `reset`: clears a safety stop.

The safety rules (safety stop, power-up wait, dry-run guard, roof sensor) apply in manual mode too.
