<?php

namespace Tests\Fakes;

use App\Device\Publisher;

class FakePublisher implements Publisher
{
    /** @var list<array{topic: string, message: array|string, retain: bool}> */
    public array $sent = [];

    public function publish(string $topic, string $message, bool $retain = false): void
    {
        $this->sent[] = [
            'topic' => $topic,
            'message' => json_decode($message, true) ?? $message,
            'retain' => $retain,
        ];
    }

    public function last(): ?array
    {
        return $this->sent[array_key_last($this->sent)] ?? null;
    }
}
