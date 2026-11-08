<?php

namespace App\Device;

interface Publisher
{
    public function publish(string $topic, string $message, bool $retain = false): void;
}
