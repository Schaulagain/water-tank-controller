# A safety stop survives a restart

When the pump hits the run limit, the controller keeps the pump off until someone sends `reset`, even across power cuts. The ESP32 will keep it in flash, and the simulator keeps it through `boot()`. Power cuts are common, and if a restart cleared the stop, a burst pipe or a jammed sensor would get a fresh run every time the power came back. The cost is that someone has to reset it by hand after every safety stop, even a false alarm.
