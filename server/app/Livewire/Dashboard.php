<?php

namespace App\Livewire;

use App\Device\Device;
use App\Device\DeviceState;
use App\Device\Settings;
use App\Models\PumpRun;
use App\Support\LevelChart;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Water tanks')]
class Dashboard extends Component
{
    public ?string $error = null;

    /** Reverb pings this when the device reports; re-rendering is all it takes. */
    #[On('echo-private:device,DeviceUpdated')]
    public function refreshState(): void {}

    public function command(string $action): void
    {
        if (! in_array($action, ['auto', 'on', 'off', 'reset'], true)) {
            return;
        }

        try {
            app(Device::class)->command($action);
            $this->error = null;
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'Couldn\'t reach the controller. Try again in a moment.';
        }
    }

    public function render(DeviceState $device, Settings $settings)
    {
        $state = $device->get();

        return view('livewire.dashboard', [
            'state' => $state,
            'online' => $device->isOnline($state),
            'settings' => $settings->all(),
            'chart' => LevelChart::lastDay(),
            'runs' => PumpRun::query()->latest('started_at')->limit(10)->get(),
        ]);
    }
}
