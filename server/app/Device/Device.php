<?php

namespace App\Device;

/**
 * Everything the app sends to the controller. The topics are described in
 * docs/device-protocol.md.
 */
class Device
{
    public function __construct(private Publisher $publisher) {}

    public static function topic(string $name): string
    {
        return config('device.mqtt.topic').'/'.$name;
    }

    /** @param 'auto'|'on'|'off'|'reset' $action */
    public function command(string $action): void
    {
        $this->send('command', ['action' => $action]);
    }

    public function sendConfig(array $config): void
    {
        $this->send('config', $config, retain: true);
    }

    public function simulate(string $action, mixed $value = null): void
    {
        $this->send('sim/control', ['action' => $action, 'value' => $value]);
    }

    private function send(string $topic, array $payload, bool $retain = false): void
    {
        $this->publisher->publish(self::topic($topic), json_encode($payload), $retain);
    }
}
