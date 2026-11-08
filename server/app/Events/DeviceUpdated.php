<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Tells open pages that something changed. It carries no data; the page
 * reads the latest state itself.
 */
class DeviceUpdated implements ShouldBroadcastNow
{
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('device');
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
