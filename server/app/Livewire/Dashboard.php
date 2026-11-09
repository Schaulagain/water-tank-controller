<?php

namespace App\Livewire;

use App\Device\Command;
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

    /** The command we're waiting on the controller to confirm, if any. */
    public ?string $pending = null;

    public ?int $pendingAt = null;

    private const CONFIRM_SECONDS = 10;

    /** Reverb pings this when the controller reports; re-rendering is all it takes. */
    #[On('echo-private:device,DeviceUpdated')]
    public function refreshState(): void {}

    public function command(string $action): void
    {
        $command = Command::tryFrom($action);

        if ($this->pending || ! $command) {
            return;
        }

        try {
            app(Device::class)->command($command);
            $this->error = null;
            $this->pending = $action;
            $this->pendingAt = now()->getTimestamp();
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'Couldn\'t send that. Try again.';
        }
    }

    /** Clears the pending command once the controller's state shows it, or gives up after a while. */
    private function settlePending(array $state): void
    {
        if (! $this->pending) {
            return;
        }

        if (Command::from($this->pending)->isConfirmedBy($state)) {
            $this->pending = null;
        } elseif (now()->getTimestamp() - $this->pendingAt >= self::CONFIRM_SECONDS) {
            $this->pending = null;
            $this->error = 'No answer from the controller.';
        }
    }

    public function render(DeviceState $device, Settings $settings)
    {
        $state = $device->get();
        $this->settlePending($state);

        return view('livewire.dashboard', [
            'state' => $state,
            'online' => $device->isOnline($state),
            'settings' => $settings->all(),
            'chart' => LevelChart::lastDay(),
            'runs' => PumpRun::query()->latest('started_at')->limit(10)->get(),
        ]);
    }
}
