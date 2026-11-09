# Controller protocol

How the controller (the ESP32, or the simulator for now) and the server talk. Everything goes through the MQTT broker. Topics start with `MQTT_TOPIC`, which is `home/water` by default. Messages are JSON unless noted.

## From the controller

### `home/water/status` (retained)

Plain text, `online` or `offline`.

The controller publishes `online` when it connects. It also sets `offline` as its MQTT "last will", so the broker announces it straight away if the controller drops off without saying goodbye.

### `home/water/state`

The controller's full picture. Sent every 10 seconds, and straight away whenever the pump, mode, manual setting, reason, fault or dry-run guard changes.

```json
{
  "roof": 63.2,
  "underground": 80.1,
  "underground_float": true,
  "pump": true,
  "pump_since": 1793193600,
  "mode": "auto",
  "manual_pump": null,
  "manual_until": null,
  "reason": "filling roof tank",
  "fault": null,
  "dry_blocked": false,
  "power_wait_until": 1793193480,
  "time": 1793193650
}
```

| Field | Meaning |
| --- | --- |
| `roof`, `underground` | Level in percent. `null` when the sensor isn't giving a usable reading |
| `underground_float` | The float switch near the bottom of the underground tank. `false` means the water is below it |
| `pump` | Whether the pump is on |
| `pump_since` | Unix time the pump started, or `null` |
| `mode` | `auto` or `manual` |
| `manual_pump` | In manual mode, whether the pump was switched on (`true`) or off (`false`). `null` in auto mode. The pump itself can still be off if a safety rule holds it |
| `manual_until` | Unix time manual mode ends by itself, or `null` |
| `reason` | Short, human-readable reason for what the pump is doing. Shown on the page as is |
| `fault` | `null`, or `max_run` during a safety stop (the pump hit the run limit). Stays until reset |
| `dry_blocked` | `true` while the dry-run guard is on |
| `power_wait_until` | Unix time the power-up wait ends |
| `time` | The controller's clock (it should sync over NTP) |

The server works out pump runs and notifications by comparing each state message to the one before, so there's no separate event topic.

## To the controller

### `home/water/command`

```json
{ "action": "on" }
```

| Action | What it does |
| --- | --- |
| `auto` | Back to auto mode |
| `on` / `off` | Manual mode with the pump on or off, until `manual_timeout_minutes` passes |
| `reset` | Clears a safety stop |

### `home/water/config` (retained)

The thresholds and timers. Retained, so the controller gets them every time it connects.

```json
{
  "roof_on": 25,
  "roof_off": 95,
  "underground_block": 15,
  "underground_resume": 25,
  "max_run_minutes": 30,
  "power_wait_seconds": 120,
  "manual_timeout_minutes": 120
}
```

The controller keeps working with its last known config (or the defaults above) when the server is down.

## Simulator only

| Topic | Direction | Payload |
| --- | --- | --- |
| `home/water/sim/control` | to the simulator | `{"action": "...", "value": ...}` with `usage` (`none`/`normal`/`heavy`), `supply`, `power`, `roof_sensor_stuck` (true/false), `roof_level` or `underground_level` (percent) |
| `home/water/sim/state` | from the simulator, retained | The real water levels and what the simulator is set to |
