<?php

namespace App\Device;

use PhpMqtt\Client\MqttClient;

/**
 * Sends one message and hangs up. Page clicks are rare enough that holding a
 * connection open from every web request isn't worth it.
 */
class MqttPublisher implements Publisher
{
    public function publish(string $topic, string $message, bool $retain = false): void
    {
        $client = new MqttClient(
            config('device.mqtt.host'),
            config('device.mqtt.port'),
            'water-web-'.bin2hex(random_bytes(3)),
        );

        $client->connect();
        $client->publish($topic, $message, 1, $retain);
        $client->disconnect();
    }
}
