<?php

namespace App\Livewire;

use App\Device\Device;
use App\Device\DeviceState;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Simulator')]
class Simulator extends Component
{
    public ?string $error = null;

    public function mount(): void
    {
        abort_unless(config('device.simulator'), 404);
    }

    #[On('echo-private:device,DeviceUpdated')]
    public function refreshState(): void {}

    public function send(string $action, mixed $value = null): void
    {
        $allowed = ['usage', 'supply', 'power', 'roof_sensor_stuck', 'roof_level', 'underground_level'];

        if (! in_array($action, $allowed, true)) {
            return;
        }

        try {
            app(Device::class)->simulate($action, $value);
            $this->error = null;
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'Couldn\'t reach the broker.';
        }
    }

    public function render(DeviceState $device)
    {
        return view('livewire.simulator', ['sim' => $device->sim()]);
    }
}
