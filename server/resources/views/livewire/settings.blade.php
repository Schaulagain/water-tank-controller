@php
    $groups = [
        ['Roof tank', 'home', [
            'roof_on' => ['Start at', '%', 1],
            'roof_off' => ['Stop at', '%', 1],
        ]],
        ['Underground tank', 'underground', [
            'underground_block' => ['Too low below', '%', 1],
            'underground_resume' => ['Allowed again at', '%', 1],
        ]],
        ['Safety', 'alert', [
            'max_run_minutes' => ['Longest run', 'min', 5],
            'power_wait_seconds' => ['Wait after power cut', 'sec', 30],
            'manual_timeout_minutes' => ['Hand control lasts', 'min', 15],
        ]],
    ];
@endphp

<div>
    <h1 class="pb-4 text-2xl font-semibold tracking-tight">Settings</h1>

    <form wire:submit="save" class="space-y-3">
        @foreach ($groups as [$group, $icon, $fields])
            <fieldset class="rounded-3xl border border-line/70 bg-card px-4 py-2">
                <legend class="sr-only">{{ $group }}</legend>
                <p class="flex items-center gap-1.5 pt-2 pb-1 text-xs font-medium text-muted" aria-hidden="true">
                    <x-icon :name="$icon" class="size-3.5" />{{ $group }}
                </p>
                @foreach ($fields as $field => [$label, $unit, $step])
                    <div class="flex items-center justify-between gap-3 border-t border-line/60 py-2.5 first-of-type:border-t-0" x-data>
                        <label for="{{ $field }}" class="text-sm">{{ $label }}</label>
                        <div class="flex items-center gap-1">
                            <button type="button" class="press grid size-8 place-items-center rounded-full bg-page text-muted hover:text-ink" aria-label="Less"
                                    x-on:click="$refs.n.stepDown(); $refs.n.dispatchEvent(new Event('input'))">
                                <x-icon name="minus" class="size-4" />
                            </button>
                            <input id="{{ $field }}" x-ref="n" type="number" inputmode="numeric" step="{{ $step }}" wire:model="{{ $field }}"
                                   class="w-12 bg-transparent text-center text-base font-medium tabular-nums outline-none">
                            <button type="button" class="press grid size-8 place-items-center rounded-full bg-page text-muted hover:text-ink" aria-label="More"
                                    x-on:click="$refs.n.stepUp(); $refs.n.dispatchEvent(new Event('input'))">
                                <x-icon name="plus" class="size-4" />
                            </button>
                            <span class="w-7 text-xs text-muted">{{ $unit }}</span>
                        </div>
                    </div>
                    @error($field)
                        <p class="page-enter -mt-1 flex items-center gap-1.5 pb-2.5 text-xs text-bad"><x-icon name="alert" class="size-3.5" />{{ $message }}</p>
                    @enderror
                @endforeach
            </fieldset>
        @endforeach

        <div class="flex items-center gap-3 pt-1"
             x-data="{ done: false }"
             x-on:settings-saved.window="done = true; setTimeout(() => done = false, 2200)">
            <button class="press grid h-12 flex-1 place-items-center rounded-2xl bg-accent font-medium text-page disabled:opacity-70" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-icon name="check" class="size-[18px]" x-show="done" x-transition.scale.origin.center />
                    <span x-text="done ? 'Saved' : 'Save'">Save</span>
                </span>
                <x-spinner wire:loading wire:target="save" class="size-5" />
            </button>
        </div>
        @if ($status)
            <p class="page-enter flex items-center gap-1.5 px-1 text-xs text-warn" role="status"><x-icon name="wifi-off" class="size-3.5" />{{ $status }}</p>
        @endif
    </form>
</div>
