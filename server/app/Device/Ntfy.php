<?php

namespace App\Device;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a push notification to the ntfy app on my phone. Does nothing until
 * NTFY_TOPIC is set.
 */
class Ntfy
{
    public function send(string $title, string $message, string $priority = 'default', array $tags = []): void
    {
        $topic = config('device.ntfy.topic');

        if (! $topic) {
            return;
        }

        try {
            Http::withHeaders([
                'Title' => $title,
                'Priority' => $priority,
                'Tags' => implode(',', $tags),
            ])->timeout(10)->withBody($message, 'text/plain')
                ->post(config('device.ntfy.url').'/'.$topic)
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('ntfy failed: '.$e->getMessage());
        }
    }
}
