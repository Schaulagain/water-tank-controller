<?php

namespace App\Console\Commands;

use App\Device\DeviceState;
use App\Device\MessageHandler;
use App\Device\Ntfy;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device:check')]
#[Description('Mark the controller offline if it has gone quiet, and send a notification if it stays that way')]
class CheckDevice extends Command
{
    public function handle(DeviceState $device, MessageHandler $handler, Ntfy $ntfy): int
    {
        $state = $device->get();

        if ($state['last_seen'] === null) {
            return self::SUCCESS;
        }

        $quietFor = now()->getTimestamp() - $state['last_seen'];

        if ($state['online'] && ! $device->isOnline($state)) {
            $handler->controllerWentOffline();
            $state = $device->get();
        }

        if (! $state['online'] && ! $state['offline_notified'] && $quietFor >= config('device.notify_offline_after_seconds')) {
            $ntfy->send(
                'Water pump controller is offline',
                'Haven\'t heard from it for '.intdiv($quietFor, 60).' minutes. It might be a power cut or a WiFi problem.',
                'default',
                ['electric_plug'],
            );
            $device->update(['offline_notified' => true]);
        }

        return self::SUCCESS;
    }
}
