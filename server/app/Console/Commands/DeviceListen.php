<?php

namespace App\Console\Commands;

use App\Device\Device;
use App\Device\MessageHandler;
use App\Device\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;

#[Signature('device:listen')]
#[Description('Listen for messages from the controller and keep the app up to date')]
class DeviceListen extends Command
{
    public function handle(MessageHandler $handler, Settings $settings): int
    {
        $client = new MqttClient(
            config('device.mqtt.host'),
            config('device.mqtt.port'),
            'water-server-'.bin2hex(random_bytes(3)),
        );

        $client->connect();
        $this->info('Connected to '.config('device.mqtt.host').':'.config('device.mqtt.port'));

        // Make sure the controller has the current settings, even if they were
        // changed while the broker was down.
        $settings->push();

        foreach (['status', 'state', 'sim/state'] as $name) {
            $client->subscribe(Device::topic($name), function (string $topic, string $message) use ($handler) {
                $handler->handle($topic, $message);
            }, 1);
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $client->interrupt());
        pcntl_signal(SIGINT, fn () => $client->interrupt());

        $client->loop(true);
        $client->disconnect();

        return self::SUCCESS;
    }
}
