## Working here

- [docs/control-logic.md](docs/control-logic.md) is the spec for both the simulator and the future ESP32 firmware. A rule change goes in the doc, `simulator/src/Controller.php` and its tests together.
- In the server code the controller is called `Device` (`app/Device/`), because Laravel already uses "controller" for HTTP controllers.
- The protocol keeps its original field names: `fault: "max_run"` is a safety stop, `dry_blocked` is the dry-run guard, `power_wait_until` is the power-up wait. Renaming a field means changing the simulator, the server and the protocol doc at once.
- Tests: `vendor/bin/phpunit` in `simulator/` and `php artisan test` in `server/`, each after `composer install`. Without local PHP, run them in the `composer:2` Docker image with the folder mounted at `/app`.

## Agent skills

### Issue tracker

Issues live in this repo's GitHub Issues, managed with the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

The five default labels: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `GLOSSARY.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.
