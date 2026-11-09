<?php

namespace Simulator;

/**
 * Settings the controller works to. The server sends these as a retained message
 * on the config topic, so the controller picks them up again after a restart.
 */
final class Config
{
    public function __construct(
        public readonly float $roofOn = 25,
        public readonly float $roofOff = 95,
        public readonly float $undergroundBlock = 15,
        public readonly float $undergroundResume = 25,
        public readonly int $maxRunMinutes = 30,
        public readonly int $powerWaitSeconds = 120,
        public readonly int $manualTimeoutMinutes = 120,
    ) {}

    public static function fromArray(array $data): self
    {
        $defaults = new self;

        return new self(
            roofOn: (float) ($data['roof_on'] ?? $defaults->roofOn),
            roofOff: (float) ($data['roof_off'] ?? $defaults->roofOff),
            undergroundBlock: (float) ($data['underground_block'] ?? $defaults->undergroundBlock),
            undergroundResume: (float) ($data['underground_resume'] ?? $defaults->undergroundResume),
            maxRunMinutes: (int) ($data['max_run_minutes'] ?? $defaults->maxRunMinutes),
            powerWaitSeconds: (int) ($data['power_wait_seconds'] ?? $defaults->powerWaitSeconds),
            manualTimeoutMinutes: (int) ($data['manual_timeout_minutes'] ?? $defaults->manualTimeoutMinutes),
        );
    }
}
