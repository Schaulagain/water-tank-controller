# Building the hardware

This guide walks through the physical side of the project: what to buy, where each part goes, how it's wired, and how to test it safely. It's written for someone who has never built anything like this before. If you can follow a recipe and use a screwdriver, you can do most of it. The one part you shouldn't do yourself is the 230V mains wiring, and the guide says clearly where that starts.

> **Status:** the software side works today with a simulator standing in for the controller. The ESP32 firmware (in `firmware/`) is the next piece, and it will follow the rules in [control-logic.md](control-logic.md). This guide describes the hardware it's being written for.

## Why I built this

A lot of houses in Nepal, mine included, have the same setup: water comes into an underground tank, and an electric pump pushes it up to a tank on the roof. The taps in the house run from the roof tank.

The pump is switched on and off by hand, and that causes three everyday problems:

1. **The roof tank overflows.** You switch the pump on, get busy, and come back to water pouring off the roof.
2. **The roof tank runs empty.** Nobody remembers to fill it until a tap runs dry, usually at the worst moment.
3. **The pump runs dry.** If the underground tank is empty, the pump spins with no water in it. That overheats the motor and can burn it out, and a new pump costs far more than this whole project.

Town water supply doesn't help, since it comes at odd hours and you never quite know how much is in the underground tank.

## How it helps

Once it's in, the system:

- **fills the roof tank by itself** when it drops low, and stops before it overflows
- **keeps the pump off when the underground tank is nearly empty**, so it can never run dry
- **shows both tank levels on your phone**, from home or anywhere else
- **lets you switch the pump on or off from your phone**
- **sends you a notification** if something looks wrong, like the pump running far too long
- **waits a couple of minutes after a power cut** before starting the motor, because power often flickers before it settles
- **keeps a history** so you can see how fast your house uses water

The decisions are made by the small board next to the pump. If your WiFi or the server goes down, the tanks still fill properly. You just can't see it on your phone until it's back.

## How it works, in plain words

There are three jobs, done by three things:

| Job | Done by | In plain words |
| --- | --- | --- |
| Measure | Two sensors and a float switch | The sensors bounce sound off the water to see how far down it is. The float switch is a backup that tells you if the underground tank is nearly empty |
| Decide | An ESP32 board | A small, cheap computer with WiFi. It reads the sensors every second and switches the pump on or off |
| Show | A server (a laptop or an always-on home computer) | Stores the readings and shows them on a web page you open on your phone |

The ESP32 can't switch a motor directly, so it switches a **relay**, which switches a **contactor**, which switches the pump. A contactor is a heavy-duty switch built for motors.

## Safety first

Please read this before buying anything.

- **The pump runs on 230V mains. That can kill.** Have a licensed electrician do every connection to the mains, the contactor's power side and the pump. Everything else in this guide is low voltage (5V) and safe to handle.
- **Switch off the MCB** and check with a tester before anyone touches the pump wiring.
- **Keep a manual changeover switch** so the pump can always be run by hand, the old way, if the electronics ever fail.
- **Keep water away from the electronics.** Everything goes in a sealed box, and cables come in from the bottom so water drips off them.
- **Test with something harmless first.** Before the pump is connected, check that the relay clicks on and off the way you expect (see [Testing](#testing-it-step-by-step)).

## Parts list

Prices are rough figures from Nepal in 2026 (Daraz, or electronics shops in New Road and Putalisadak). Check locally.

| Part | What it's for | Approx. Rs |
| --- | --- | --- |
| **ESP32 DevKit** (ESP32-WROOM-32). Get the **WROOM-32U** version with an external antenna if WiFi is weak near the pump | The brain | 800 – 1,200 |
| **JSN-SR04T waterproof ultrasonic sensor** × 2 | Measures how full each tank is | 1,000 – 1,500 each |
| **Float switch** (vertical, plastic) | Backup that blocks the pump when the underground tank is nearly empty | 200 – 400 |
| **5V relay module**, 1 channel, with an optocoupler | Lets the 3.3V board switch the contactor's coil | 150 – 250 |
| **Contactor**, 230V coil, rated 9A or more (AC-3) | The heavy-duty switch for the motor | 1,500 – 3,000 |
| **Thermal overload relay** matched to your motor's current | Cuts the motor if it overheats. Strongly recommended | 1,500 – 2,500 |
| **Changeover switch** (manual / auto) | Lets you go back to manual any time | 300 – 600 |
| **5V power supply**: an HLK-PM01 module, or a 5V 2A phone charger | Powers the ESP32 | 300 – 500 |
| **IP65 junction box** with cable glands | Keeps the electronics dry | 400 – 800 |
| **Shielded 4-core cable** (CAT6 works) | Connects the sensors to the ESP32 | 40 – 60 per metre |
| **Resistors**: 1kΩ × 2, 2kΩ × 2 | Protect the ESP32 from the sensors' 5V signal (see below) | ~50 |
| Terminal blocks, jumper wires, small PCB or breadboard | Connections | ~200 |

**Total:** roughly Rs 8,000 – 13,000, plus the electrician.

If the roof tank is more than about 10 m of cable away from the pump, add a **second ESP32** for the roof (about Rs 1,000) and skip the long cable. See [The roof sensor cable](#the-roof-sensor-cable).

### Tools

- A **multimeter**, for checking voltages and connections. Even a cheap one is enough
- Screwdrivers, a wire stripper and a small drill
- A **soldering iron** helps, though screw terminals work too
- A **USB cable** that carries data, for loading the code onto the ESP32. Some cables only charge

## Where everything goes

```
   ROOF TANK
   ┌───────────┐  ← sensor 1 in the lid, pointing down at the water
   │~~~~~~~~~~~│
   └───────────┘
         │  shielded cable down the wall
         │
 ┌───────┴───────────┐
 │  Sealed box       │  on the wall next to the pump's switch
 │  ESP32, relay,    │────► contactor ────► pump
 │  5V supply        │      (electrician)
 └───────┬───────────┘
         │
   UNDERGROUND TANK
   ┌───────────┐  ← sensor 2 in the lid, pointing down
   │~~~~~~~~~~~│
   │   ◯       │  ← float switch, a little above the pump's suction pipe
   └───────────┘
```

### The box

Mount the sealed box on a wall near the pump's switch and MCB, out of reach of splashes. Before you fix it in place, stand there with your phone and check the WiFi signal. If it's weak, use the ESP32-WROOM-32U with an external antenna, or add a WiFi extender.

### The ultrasonic sensors

Each sensor has a small probe on a cable and a little circuit board.

- Drill a hole in the tank lid that fits the probe snugly (measure yours, it's usually around 22–25 mm), and push the probe in so it points **straight down** at the water.
- The sensor can't see anything in the first **20–25 cm** below it. Place it so the water never comes closer than that, even when the tank is full.
- Keep it **away from the inlet pipe**. Falling water and splashes confuse it.
- Keep the probe's circuit board dry, either inside the box or in a small sealed case near the tank.

### The float switch

The float switch hangs in the underground tank, a little **above the pump's suction pipe** (the foot valve). When the water drops below it, it tells the controller the tank is nearly empty, and the dry-run guard keeps the pump off. This is a backup for the underground sensor, in case it ever gives a wrong reading.

### The roof sensor cable

The roof sensor needs four wires running down to the box. Up to about 10 m, shielded 4-core cable (or CAT6) works well. Beyond that, the signal gets unreliable, and a second ESP32 on the roof that sends its reading over WiFi is the easier fix.

## Wiring

### Low-voltage side (safe to do yourself)

| From | To ESP32 pin | Notes |
| --- | --- | --- |
| Roof sensor **VCC** | **VIN** (5V) | |
| Roof sensor **GND** | **GND** | |
| Roof sensor **TRIG** | **GPIO 23** | |
| Roof sensor **ECHO** | **GPIO 22**, through a voltage divider | See below |
| Underground sensor **VCC** | **VIN** (5V) | |
| Underground sensor **GND** | **GND** | |
| Underground sensor **TRIG** | **GPIO 19** | |
| Underground sensor **ECHO** | **GPIO 18**, through a voltage divider | See below |
| Float switch, wire 1 | **GPIO 27** | The ESP32 turns on its internal pull-up |
| Float switch, wire 2 | **GND** | |
| Relay module **VCC** | **VIN** (5V) | |
| Relay module **GND** | **GND** | |
| Relay module **IN** | **GPIO 26** | |
| 5V power supply **+** | **VIN** | |
| 5V power supply **−** | **GND** | |

These pins were picked because they stay quiet while the ESP32 starts up. Some ESP32 pins flicker during boot, and a flicker on the relay pin would briefly start the pump.

**The voltage divider, and why it matters.** The sensors send their ECHO signal at 5V, and the ESP32's pins only handle 3.3V. Without the divider, it may work for a while and then slowly damage the ESP32. For each sensor:

```
sensor ECHO ──[ 1kΩ ]──┬──► ESP32 pin
                       │
                    [ 2kΩ ]
                       │
                      GND
```

That brings 5V down to about 3.3V.

### Mains side (electrician only)

Show this section to your electrician.

- **Power path:** MCB → changeover switch.
  - **Manual position:** the pump's existing switch, exactly as it works today.
  - **Auto position:** contactor main contacts → thermal overload relay → pump.
- **Contactor coil:** live → relay module's **NO** contact → overload relay's **NC** contact → coil **A1**. Coil **A2** → neutral.
- The contactor should be rated **AC-3, at least 9A** for a pump of up to 1 HP, and the overload relay set to the current on the motor's nameplate.

## Connecting it to the server

The ESP32 joins your home WiFi and sends its readings to the MQTT broker (Mosquitto) running on your laptop or home server, on port **1883**.

1. **Give the server a fixed address.** In your router's settings, find "DHCP reservation" (sometimes "static lease") and reserve an address for the laptop or home server. If the address changes, the controller can't find it anymore.
2. **Leave port 1883 open** on that machine if it has a firewall turned on.
3. **Keep the server running.** If the laptop sleeps, the pump still works, because the controller makes the decisions. You just won't see anything on your phone until it's back. This is why I'm moving it to an always-on home server.
4. **Reach it from outside** with [Tailscale](https://tailscale.com) on the server and your phone. Nothing needs to be opened to the internet. The controller doesn't need Tailscale; it only talks to the server on your home network.

The broker has no password at the moment, which is fine for testing at home. Adding one before the real board goes in is on the to-do list.

## Calibrating the tanks

A sensor measures **distance**. To turn that into "how full", the controller needs two numbers for each tank:

- **Empty distance:** from the sensor down to the bottom of the tank (or the lowest water level you care about)
- **Full distance:** from the sensor down to the water when the tank is full

Measure both with a tape measure when you install the sensor. Then:

```
percent full = (empty distance − reading) ÷ (empty distance − full distance) × 100
```

For example, if the sensor is 150 cm above the bottom and 30 cm above the full level, a reading of 90 cm means (150 − 90) ÷ (150 − 30) × 100 = **50% full**.

The calibration values will be settings in the app once the firmware is written.

## Testing it, step by step

Go slowly here. Each step only adds one new thing.

1. **Board only.** Plug the ESP32 into the laptop with USB, load the firmware, and check that it joins WiFi and the page shows it as **Live**.
2. **Sensors on the bench.** Connect both sensors and hold them over a bucket or the floor. Move them up and down and watch the levels change on the page.
3. **Float switch.** Lift and drop it by hand. The page should show "Dry-run guard on" while the float is down.
4. **Relay, no pump.** Press **On** and **Off** on the page and listen for the relay clicking. Nothing is connected to it yet.
5. **Install the sensors** in the tanks and check that the levels on the page match what you see with your own eyes.
6. **Electrician connects the contactor and pump**, with the changeover switch on **Manual**.
7. **First automatic fill, supervised.** Switch to **Auto** and watch a full fill from start to finish. Note how long it takes.
8. **Set the run limit** on the Settings page to about **1.5 times** that fill time. If the pump ever runs longer, something is wrong (a leak, a jammed sensor), and it'll stop and tell you.

## When something goes wrong

| What you see | Likely cause | What to try |
| --- | --- | --- |
| Page says **Offline** | Board has no power, or WiFi is weak, or the server's address changed | Check the 5V supply, check WiFi signal by the box, check the router reservation |
| A level jumps around | Sensor is too close to the water, near the inlet pipe, or tilted | Move it away from the inlet, make sure it points straight down, keep 25 cm clearance |
| A level shows **–** | The sensor isn't giving a reading | Check its four wires and the voltage divider |
| Pump never starts | The underground tank is below the dry-run level, or the float switch is stuck down | Check the underground level on the page, and free the float |
| "Safety stop" | The pump ran longer than the run limit | Look for a leak or a jammed sensor, then press Reset. If fills are just slow, raise the setting |
| Relay clicks but the pump doesn't run | Changeover switch is on Manual, or a contactor or overload problem | Check the switch, then ask your electrician |

## Using this for your own house

Everything here is MIT licensed, so you're free to copy it, change it and use it. Tank sizes, thresholds and timings are all settings, so it should fit most houses with a roof tank and an underground tank. If you build one, I'd love to hear how it went. Open an issue on the repo with photos or questions.

## Where this information comes from

To be upfront about it: this guide was written by **Claude**, Anthropic's AI model, working in Claude Code. I asked the questions, made the decisions and gave feedback along the way. Most of what's here comes from Claude's general knowledge of the ESP32, ultrasonic sensors and pump wiring, so please treat it as a well-researched starting point and double-check anything that matters for your house.

Here's what was checked against a real source, and what wasn't:

| Topic | Where it comes from |
| --- | --- |
| Which ESP32 pins are safe to use (stay quiet at boot, not strapping pins) | Checked against the [ESP32 pinout reference](https://randomnerdtutorials.com/esp32-pinout-reference-gpios/) by Random Nerd Tutorials |
| The voltage divider for the 5V ECHO signal | Standard 1k / 2k divider maths (5V × 2/3 ≈ 3.3V) |
| Sensor blind zone (20–25 cm) and the ~10 m cable limit | General knowledge of the JSN-SR04T. Sellers' datasheets vary, so check the one for your sensor |
| Contactor, overload relay and changeover wiring | Common practice for single-phase pump control. **Your electrician should confirm it against local rules** |
| Prices | Rough estimates. They haven't been checked against shops, and prices change, so ask around |
| How the controller, server and phone talk to each other | This project's own design: see [device-protocol.md](device-protocol.md) and [control-logic.md](control-logic.md) |

### Further reading

These are the official docs for the main pieces, if you want to go deeper:

- [ESP32 datasheet](https://www.espressif.com/sites/default/files/documentation/esp32_datasheet_en.pdf) (Espressif)
- [MQTT](https://mqtt.org) and the [Mosquitto broker docs](https://mosquitto.org/documentation/)
- [Tailscale docs](https://tailscale.com/kb)
- [ntfy docs](https://docs.ntfy.sh), for phone notifications
