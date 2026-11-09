<?php

namespace App\Device;

/** What the page can tell the controller to do. See docs/device-protocol.md. */
enum Command: string
{
    case Auto = 'auto';
    case On = 'on';
    case Off = 'off';
    case Reset = 'reset';

    /** Whether the controller's latest state shows it has carried this command out. */
    public function isConfirmedBy(array $state): bool
    {
        return match ($this) {
            self::Auto => $state['mode'] === 'auto',
            self::On => $state['mode'] === 'manual' && $state['manual_pump'] === true,
            self::Off => $state['mode'] === 'manual' && $state['manual_pump'] === false,
            self::Reset => $state['fault'] === null,
        };
    }
}
