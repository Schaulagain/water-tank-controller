@php
    $tile = 'press group relative flex flex-col items-start gap-3 rounded-2xl p-3.5 text-left text-sm font-medium ring-1 transition disabled:cursor-not-allowed';
    $chip = 'press group flex items-center gap-1.5 rounded-full bg-page px-3 py-2 text-xs font-medium text-muted hover:text-ink disabled:opacity-50';
    $state = fn (bool $on) => $on ? 'bg-ink text-page ring-ink' : 'bg-card text-ink ring-line/70 hover:ring-ink/30 disabled:opacity-50';
@endphp

<div class="space-y-3">
    <header class="flex items-center justify-between pb-1">
        <h1 class="text-2xl font-semibold tracking-tight">Simulator</h1>
        @if ($sim)
            <span class="rounded-full bg-card px-3 py-1.5 text-xs font-medium tabular-nums text-muted ring-1 ring-line/70" title="Water moves this much faster than real life">{{ $sim['speed'] }}× speed</span>
        @endif
    </header>

    @if ($error)
        <p class="page-enter flex items-center gap-2 rounded-2xl bg-card px-4 py-3 text-sm ring-1 ring-bad/30" role="alert">
            <x-icon name="alert" class="size-4 text-bad" />{{ $error }}
        </p>
    @endif

    @if (! $sim)
        <div class="grid place-items-center gap-2 rounded-3xl border border-line/70 bg-card py-14 text-muted">
            <x-spinner class="size-6" />
            <p class="text-xs">Waiting for the simulator</p>
        </div>
    @else
        <section class="grid grid-cols-2 gap-3">
            @foreach ([['Roof', 'home', 'roof', 'bg-roof'], ['Underground', 'underground', 'underground', 'bg-underground']] as [$label, $icon, $key, $color])
                <div class="rounded-3xl border border-line/70 bg-card p-4">
                    <p class="flex items-center gap-1.5 text-xs font-medium text-muted"><x-icon :name="$icon" class="size-3.5" />{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ round($sim[$key]) }}<span class="text-sm text-muted">%</span></p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-page">
                        <div class="h-full rounded-full {{ $color }} transition-[width] duration-700" style="width: {{ $sim[$key] }}%"></div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="rounded-3xl border border-line/70 bg-card p-4">
            <p class="text-xs font-medium text-muted">Water use</p>
            <div class="mt-3 grid grid-cols-3 gap-2">
                @foreach (['none' => ['Away', 'moon'], 'normal' => ['Normal', 'droplet'], 'heavy' => ['Heavy', 'waves']] as $value => [$label, $icon])
                    <button wire:click="send('usage', '{{ $value }}')" wire:loading.attr="disabled" wire:target="send"
                            aria-pressed="{{ $sim['usage'] === $value ? 'true' : 'false' }}" class="{{ $tile }} {{ $state($sim['usage'] === $value) }}">
                        <x-icon :name="$icon" class="size-5 group-data-loading:hidden" />
                        <x-spinner class="hidden size-5 group-data-loading:block" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-line/70 bg-card p-4">
            <p class="text-xs font-medium text-muted">Conditions</p>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <button wire:click="send('supply', {{ $sim['supply'] ? 'false' : 'true' }})" wire:loading.attr="disabled" wire:target="send"
                        aria-pressed="{{ $sim['supply'] ? 'true' : 'false' }}" class="{{ $tile }} {{ $state($sim['supply']) }}">
                    <x-icon name="pipe" class="size-5 group-data-loading:hidden" />
                    <x-spinner class="hidden size-5 group-data-loading:block" />
                    Town water
                </button>
                <button wire:click="send('power', {{ $sim['power'] ? 'false' : 'true' }})" wire:loading.attr="disabled" wire:target="send"
                        aria-pressed="{{ $sim['power'] ? 'false' : 'true' }}" class="{{ $tile }} {{ $state(! $sim['power']) }}">
                    <x-icon :name="$sim['power'] ? 'zap' : 'zap-off'" class="size-5 group-data-loading:hidden" />
                    <x-spinner class="hidden size-5 group-data-loading:block" />
                    {{ $sim['power'] ? 'Power on' : 'Power cut' }}
                </button>
                <button wire:click="send('roof_sensor_stuck', {{ $sim['roof_sensor_stuck'] ? 'false' : 'true' }})" wire:loading.attr="disabled" wire:target="send"
                        aria-pressed="{{ $sim['roof_sensor_stuck'] ? 'true' : 'false' }}" class="{{ $tile }} {{ $state($sim['roof_sensor_stuck']) }}">
                    <x-icon name="activity" class="size-5 group-data-loading:hidden" />
                    <x-spinner class="hidden size-5 group-data-loading:block" />
                    {{ $sim['roof_sensor_stuck'] ? 'Sensor jammed' : 'Sensor OK' }}
                </button>
            </div>
        </section>

        <section class="rounded-3xl border border-line/70 bg-card p-4">
            <p class="text-xs font-medium text-muted">Jump to</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ([['roof_level', 20, 'arrow-down', 'Roof 20%'], ['roof_level', 90, 'arrow-up', 'Roof 90%'], ['underground_level', 8, 'arrow-down', 'Underground 8%'], ['underground_level', 100, 'arrow-up', 'Underground full']] as [$action, $value, $icon, $label])
                    <button wire:click="send('{{ $action }}', {{ $value }})" wire:loading.attr="disabled" wire:target="send" class="{{ $chip }}">
                        <x-icon :name="$icon" class="size-3.5 group-data-loading:hidden" />
                        <x-spinner class="hidden size-3.5 group-data-loading:block" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </section>
    @endif
</div>
