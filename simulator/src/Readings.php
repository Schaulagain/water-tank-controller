<?php

namespace Simulator;

/**
 * What the sensors say right now. A level is null when its sensor isn't giving
 * a usable reading.
 */
final class Readings
{
    public function __construct(
        public readonly ?float $roof,
        public readonly ?float $underground,
        public readonly bool $undergroundFloat,
    ) {}
}
