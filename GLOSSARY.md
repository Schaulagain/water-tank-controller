# Water tank controller

A system that keeps a house's roof tank filled from its underground tank, keeps the pump safe, and shows what's happening on a phone.

## The house

**Roof tank**:
The tank on the roof that feeds the taps. The pump fills it.
_Avoid_: overhead tank, top tank

**Underground tank**:
The tank below ground that town water fills and the pump draws from.
_Avoid_: sump, lower tank

**Pump**:
The electric motor that moves water from the underground tank to the roof tank.
_Avoid_: motor (except when talking about wiring)

## The controller

**Controller**:
The box next to the pump that reads the tanks and switches the pump. It makes every on/off decision itself. In the code it's called `Device`, because Laravel already uses "controller" for code that handles web requests.
_Avoid_: device (in prose), board, unit

**Simulator**:
A program that stands in for the controller until the hardware is installed, and behaves the same way from the server's side.
_Avoid_: mock, fake device

**Reading**:
One measurement of both tank levels, as a percentage full.
_Avoid_: sample, data point

**Pump run**:
One stretch of the pump being on, from start to stop, with the reason for each.
_Avoid_: cycle, session

## Modes

**Auto mode**:
The controller starts and stops the pump on its own, using the start level and stop level.
_Avoid_: automatic, normal mode

**Manual mode**:
The pump is switched on or off from the page. It returns to auto mode by itself after a set time.
_Avoid_: by hand, hand control, override

## Levels

**Start level**:
The roof tank level at or below which auto mode starts the pump.
_Avoid_: low threshold, on level

**Stop level**:
The roof tank level at which the pump stops.
_Avoid_: high threshold, full level, off level

**Dry-run level**:
The underground tank level below which the dry-run guard turns on.
_Avoid_: block level, minimum level

**Resume level**:
The underground tank level the water has to reach again before the dry-run guard turns off.
_Avoid_: recovery level

## Safety

**Dry-run guard**:
Holds the pump off while the underground tank is too low to pump from, so the pump never runs without water.
_Avoid_: dry block, dry-run block, too low to pump

**Float switch**:
A backup switch near the bottom of the underground tank that turns the dry-run guard on if the water drops below it, whatever the level sensor says.
_Avoid_: float sensor, backup sensor

**Run limit**:
The longest the pump may run in one go before a safety stop.
_Avoid_: max run, maximum run time, longest run

**Safety stop**:
The pump being stopped because it hit the run limit. It stays stopped, even through a restart, until someone resets it.
_Avoid_: fault, stopped for safety, lockout

**Power-up wait**:
The pause after the controller powers up before it may start the pump, because mains power often flickers before it settles.
_Avoid_: power wait, startup delay, wait after power cut
