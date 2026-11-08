@php
    $groups = [
        'Roof tank' => [
            'roof_on' => ['Start filling at', '%', 'The pump turns on when the roof tank drops to this level.'],
            'roof_off' => ['Stop filling at', '%', 'The pump turns off when the roof tank reaches this level.'],
        ],
        'Underground tank' => [
            'underground_block' => ['Too low to pump below', '%', 'The pump won\'t run while the underground tank is under this level.'],
            'underground_resume' => ['Allowed again from', '%', 'After being too low, the pump waits until the tank is back up to here.'],
        ],
        'Safety' => [
            'max_run_minutes' => ['Longest run', 'min', 'If the pump runs this long, it stops and waits for you to reset it. Set it a bit above a normal fill.'],
            'power_wait_seconds' => ['Wait after power comes back', 'sec', 'Power often flickers before it settles, so the pump waits this long first.'],
            'manual_timeout_minutes' => ['Hand control lasts', 'min', 'After switching the pump by hand, it goes back to auto after this long.'],
        ],
    ];
@endphp

<div>
    <h1 class="text-xl font-semibold">Settings</h1>
    <p class="mt-1 text-sm text-muted">These are sent to the controller, which keeps working with them even if this page or the server is down.</p>

    <form wire:submit="save" class="mt-5 space-y-4">
        @foreach ($groups as $group => $fields)
            <fieldset class="rounded-xl border border-line bg-card p-4">
                <legend class="sr-only">{{ $group }}</legend>
                <h2 class="font-semibold">{{ $group }}</h2>
                <div class="mt-3 space-y-4">
                    @foreach ($fields as $field => [$label, $unit, $help])
                        <label class="block">
                            <span class="flex items-center justify-between gap-3">
                                <span class="text-sm font-medium">{{ $label }}</span>
                                <span class="flex items-center gap-1.5">
                                    <input type="number" inputmode="numeric" wire:model="{{ $field }}"
                                           class="w-20 rounded-lg border border-line bg-page px-2 py-1.5 text-right text-base tabular-nums outline-none focus:border-ink">
                                    <span class="w-7 text-sm text-muted">{{ $unit }}</span>
                                </span>
                            </span>
                            <span class="mt-1 block text-xs text-muted">{{ $help }}</span>
                            @error($field) <span class="mt-1 block text-sm text-bad">{{ $message }}</span> @enderror
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <button class="rounded-lg bg-accent px-5 py-2.5 font-medium text-page disabled:opacity-60" wire:loading.attr="disabled">Save</button>
            @if ($status)
                <p class="text-sm text-muted" role="status">{{ $status }}</p>
            @endif
        </div>
    </form>
</div>
