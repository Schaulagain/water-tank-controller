<?php

/*
 * Pretends to be the ESP32. It talks to the broker exactly the way the real
 * board will (see docs/device-protocol.md), so the server can't tell the
 * difference.
 */

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Simulator\Config;
use Simulator\Controller;
use Simulator\World;

require __DIR__.'/../vendor/autoload.php';

$host = getenv('MQTT_HOST') ?: 'localhost';
$port = (int) (getenv('MQTT_PORT') ?: 1883);
$prefix = rtrim(getenv('MQTT_TOPIC') ?: 'home/water', '/');
$speed = (float) (getenv('SIM_SPEED') ?: 10);
$heartbeat = 10;

$world = new World($speed);
$controller = new Controller(new Config, time());

$settings = (new ConnectionSettings)
    ->setKeepAliveInterval(15)
    ->setLastWillTopic("$prefix/status")
    ->setLastWillMessage('offline')
    ->setLastWillQualityOfService(1)
    ->setRetainLastWill(true);

$client = new MqttClient($host, $port, 'water-sim-'.bin2hex(random_bytes(3)));
$client->connect($settings, true);
$client->publish("$prefix/status", 'online', 1, true);

fwrite(STDOUT, "simulator connected to $host:$port, topic $prefix, speed x$speed\n");

$json = fn (array $data) => json_encode($data, JSON_UNESCAPED_SLASHES);
$decode = fn (string $message) => is_array($data = json_decode($message, true)) ? $data : [];

$client->subscribe("$prefix/config", function ($topic, $message) use ($controller, $decode) {
    $controller->config = Config::fromArray($decode($message));
    fwrite(STDOUT, "config updated\n");
}, 1);

$client->subscribe("$prefix/command", function ($topic, $message) use ($controller, $world, $decode, &$forcePublish) {
    if (! $world->power) {
        return; // the real board is off during a power cut, so it never sees the command
    }

    $controller->command((string) ($decode($message)['action'] ?? ''), time());
    $forcePublish = true;
    fwrite(STDOUT, "command: $message\n");
}, 1);

$client->subscribe("$prefix/sim/control", function ($topic, $message) use ($client, $prefix, $world, $controller, $decode, &$forcePublish) {
    $data = $decode($message);
    $wasPowered = $world->power;
    $world->control((string) ($data['action'] ?? ''), $data['value'] ?? null);

    if ($wasPowered && ! $world->power) {
        $world->pump = false;
        $client->publish("$prefix/status", 'offline', 1, true);
    } elseif (! $wasPowered && $world->power) {
        $controller->boot(time());
        $client->publish("$prefix/status", 'online', 1, true);
    }

    $forcePublish = true;
    fwrite(STDOUT, "sim: $message\n");
}, 1);

$forcePublish = true;
$lastTick = microtime(true);
$lastState = 0;
$lastSignature = '';

$client->registerLoopEventHandler(function (MqttClient $client) use (
    $world, $controller, $prefix, $heartbeat, $json, &$lastTick, &$lastState, &$lastSignature, &$forcePublish
) {
    $now = microtime(true);
    if ($now - $lastTick < 1) {
        return;
    }

    $world->step($now - $lastTick);
    $lastTick = $now;

    if (! $world->power) {
        $world->pump = false;
    } else {
        $readings = $world->readings();
        $world->pump = $controller->tick(time(), $readings);

        $state = [
            'roof' => $readings->roof,
            'underground' => $readings->underground,
            'underground_float' => $readings->undergroundFloat,
            ...$controller->toArray(),
            'time' => time(),
        ];

        // Send straight away when something meaningful changes, otherwise every few seconds.
        $signature = $json(array_intersect_key($state, array_flip(['pump', 'mode', 'reason', 'fault', 'dry_blocked'])));

        if ($forcePublish || $signature !== $lastSignature || $now - $lastState >= $heartbeat) {
            $client->publish("$prefix/state", $json($state), 0, false);
            $lastSignature = $signature;
            $lastState = $now;
        }
    }

    if ($forcePublish || (int) $now % $heartbeat === 0) {
        $client->publish("$prefix/sim/state", $json($world->toArray()), 0, true);
    }

    $forcePublish = false;
});

pcntl_async_signals(true);
foreach ([SIGINT, SIGTERM] as $signal) {
    pcntl_signal($signal, function () use ($client, $prefix) {
        $client->publish("$prefix/status", 'offline', 1, true);
        $client->interrupt();
    });
}

$client->loop(true);
$client->disconnect();
