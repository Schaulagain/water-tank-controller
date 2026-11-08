# Control logic

What the controller does every second. It's implemented in [`simulator/src/Controller.php`](../simulator/src/Controller.php) and tested in [`simulator/tests/ControllerTest.php`](../simulator/tests/ControllerTest.php). The ESP32 firmware will follow the same rules, so this page is the spec for both.

The logic lives on the controller. The server only shows what's happening and sends commands, so the tanks keep filling properly when the WiFi or the server is down.

## Each tick

1. **Manual timeout.** In manual mode past `manual_until`, go back to auto.
2. **Dry block.** Block the pump if the underground level is below `underground_block`, the underground sensor isn't reading, or the float switch has dropped. Once blocked, it stays blocked until the level is back to `underground_resume`.
3. **Decide**, taking the first rule that applies:

   | Situation | Pump | Reason shown |
   | --- | --- | --- |
   | A fault is set | off | stopped for safety, needs a reset |
   | Less than `power_wait_seconds` since power-up | off | waiting after power came back |
   | Dry blocked | off | underground tank too low |
   | Roof sensor not reading | off | roof sensor not reading |
   | Manual, on, roof at or above `roof_off` | off, and manual on is cancelled | roof tank full |
   | Manual | as set | turned on / off by hand |
   | Auto, pump running, roof below `roof_off` | on | filling roof tank |
   | Auto, pump running, roof at `roof_off` | off | roof tank full |
   | Auto, pump off, roof at or below `roof_on` | on | roof tank low |
   | Auto, pump off | off | roof tank has enough water |

4. **Longest run.** If the pump has been on for `max_run_minutes`, switch it off and set the `max_run` fault. That covers a jammed sensor, a burst pipe or anything else that stops the roof tank filling.

## Power cuts

When the power goes, the board goes with it. On power-up it starts in auto with the pump off and waits `power_wait_seconds` before running, because the supply often flickers before it settles.

A fault is the one thing that survives a restart. The real board keeps it in flash, so cycling the power can't hide a problem.

## Commands

- `on` / `off`: manual mode, until `manual_timeout_minutes` passes or `auto` is sent.
- `auto`: back to the thresholds.
- `reset`: clears the fault.

The safety rules (fault, power-up wait, dry block, roof sensor) apply in manual mode too.
