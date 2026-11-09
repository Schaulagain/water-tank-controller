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
    /** @var array<string, int> One value per key in Settings::DEFAULTS. */
    public array $values = [];

    public ?string $status = null;

    public function mount(Settings $settings): void
    {
        $this->values = $settings->all();
    }

    protected function rules(): array
    {
        return Settings::rules('values.');
    }

    protected function messages(): array
    {
        return [
            'values.roof_off.gt' => 'This needs to be higher than the level that turns the pump on.',
            'values.underground_resume.gt' => 'This needs to be higher than the dry-run level.',
        ];
    }

    public function save(Settings $settings): void
    {
        $values = $this->validate()['values'];

        try {
            $settings->save($values);
            $this->status = null;
            $this->dispatch('settings-saved');
        } catch (\Throwable $e) {
            report($e);
            $this->status = 'Saved. The controller gets it once it reconnects.';
        }
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
