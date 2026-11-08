<?php

namespace App\Livewire;

use App\Device\Settings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings')]
class SettingsPage extends Component
{
    public int $roof_on;

    public int $roof_off;

    public int $underground_block;

    public int $underground_resume;

    public int $max_run_minutes;

    public int $power_wait_seconds;

    public int $manual_timeout_minutes;

    public ?string $status = null;

    public function mount(Settings $settings): void
    {
        $this->fill($settings->all());
    }

    protected function rules(): array
    {
        return Settings::rules();
    }

    protected function messages(): array
    {
        return [
            'roof_off.gt' => 'This needs to be higher than the level that turns the pump on.',
            'underground_resume.gt' => 'This needs to be higher than the level that blocks the pump.',
        ];
    }

    public function save(Settings $settings): void
    {
        $values = $this->validate();

        try {
            $settings->save($values);
            $this->status = 'Saved and sent to the controller.';
        } catch (\Throwable $e) {
            report($e);
            $this->status = 'Saved. Couldn\'t reach the controller just now, so it\'ll get the new settings when the listener reconnects.';
        }
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
