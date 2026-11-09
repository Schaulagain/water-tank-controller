<?php

return [

    // The MQTT broker the controller (or the simulator) talks to.
    'mqtt' => [
        'host' => env('MQTT_HOST', 'localhost'),
        'port' => (int) env('MQTT_PORT', 1883),
        'topic' => rtrim(env('MQTT_TOPIC', 'home/water'), '/'),
    ],

    // Save a level reading at most this often, and keep readings this long.
    'reading_interval_seconds' => 30,
    'keep_readings_days' => 30,

    // The controller reports every 10 seconds. After this long without hearing
    // from it, the page shows it as offline.
    'offline_after_seconds' => 60,

    // ...and after this long, a notification goes out.
    'notify_offline_after_seconds' => 300,

    // Shows the simulator page. Turn off once the real controller is in.
    'simulator' => (bool) env('SIMULATOR_ENABLED', true),

    'ntfy' => [
        'url' => rtrim(env('NTFY_URL', 'https://ntfy.sh'), '/'),
        'topic' => env('NTFY_TOPIC'),
    ],

    // Browsers connect to Reverb on this port, on whatever host they used to
    // open the page. That way it works on localhost and over Tailscale alike.
    'reverb_public_port' => (int) env('REVERB_PUBLIC_PORT', 6001),

];
