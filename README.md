# water-tank-controller

A small system to keep an eye on the water tanks at home and run the water pump from my phone.

The pump fills the roof tank from the underground tank. Right now it's switched on and off by hand, which means forgetting it and overflowing the roof tank, or forgetting to fill it at all.

## What it should do

- Show the water level in both tanks on a web page (iPhone first)
- Turn the pump on when the roof tank gets low and off when it's full
- Keep the pump off when the underground tank is nearly empty, so it never runs dry
- Let me switch the pump on or off by hand from the page
- Work from anywhere through Tailscale, same as my [homelab](https://github.com/Schaulagain/my-homelab)

## Hardware (planned)

| Part | Job |
| --- | --- |
| ESP32 board | Reads the sensors, runs the on/off logic, switches the contactor |
| Level sensor in each tank | Tells the ESP32 how full each tank is |
| Contactor | Switches the pump's 230V supply |
| 5V power supply | Powers the ESP32 |

The pump is a single-phase motor of about 0.5 to 1 HP. An electrician will wire the contactor to the mains. I'm doing the low-voltage side and the code.

## How it decides

The automatic logic lives on the ESP32, so the tanks keep filling properly even if the WiFi or the server is down. The web page shows what's happening and lets me override it.

## Status

Planning. Still deciding which sensors to use and where the web page runs.
