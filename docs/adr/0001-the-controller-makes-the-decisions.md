# The controller makes every pump decision

The controller next to the pump decides when the pump runs, using the rules in [control-logic.md](../control-logic.md). The server only shows what's happening, stores history and passes on commands and settings. Running the logic on the server would be simpler to change, but then a WiFi drop or a server restart could leave the pump running or the roof tank empty. Keeping it on the controller means the tanks keep working with nothing else switched on, so the simulator and the firmware both have to follow the same rules.
