@php
    $btn = 'rounded-lg border border-line px-3 py-2 text-sm font-medium hover:bg-band';
    $on = 'bg-band ring-1 ring-ink/30';
@endphp

<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold">Simulator</h1>
        <p class="mt-1 text-sm text-muted">
            A pretend controller stands in for the ESP32 until the hardware arrives. Change what's happening in the house here and watch the Tanks page react.
            @if ($sim)
                Water moves {{ $sim['speed'] }}× faster than real life.
            @endif
        </p>
    </div>

    @if ($error)
        <p class="rounded-xl border border-bad/50 bg-card p-4 text-sm" role="alert">{{ $error }}</p>
    @endif

    @if (! $sim)
        <p class="rounded-xl border border-line bg-card p-4 text-sm text-muted">The simulator hasn't reported in yet. Is its container running?</p>
    @else
        <section class="grid grid-cols-2 gap-3 rounded-xl border border-line bg-card p-4 text-sm">
            <p class="col-span-2 text-xs text-muted">Actual water in the tanks (the sensors add a little noise)</p>
            <p><span class="text-muted">Roof</span> <span class="ml-1 text-lg font-semibold tabular-nums">{{ round($sim['roof']) }}%</span></p>
            <p><span class="text-muted">Underground</span> <span class="ml-1 text-lg font-semibold tabular-nums">{{ round($sim['underground']) }}%</span></p>
        </section>

        <section class="rounded-xl border border-line bg-card p-4">
            <h2 class="font-semibold">Water use in the house</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (['none' => 'Nobody home', 'normal' => 'Normal', 'heavy' => 'Laundry day'] as $value => $label)
                    <button wire:click="send('usage', '{{ $value }}')" @class([$btn, $on => $sim['usage'] === $value])>{{ $label }}</button>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-line bg-card p-4">
            <h2 class="font-semibold">Town water supply</h2>
            <p class="mt-1 text-xs text-muted">When it's flowing, it slowly fills the underground tank.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button wire:click="send('supply', true)" @class([$btn, $on => $sim['supply']])>Flowing</button>
                <button wire:click="send('supply', false)" @class([$btn, $on => ! $sim['supply']])>Not flowing</button>
            </div>
        </section>

        <section class="rounded-xl border border-line bg-card p-4">
            <h2 class="font-semibold">Things going wrong</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($sim['power'])
                    <button wire:click="send('power', false)" class="{{ $btn }}">Cut the power</button>
                @else
                    <button wire:click="send('power', true)" class="{{ $btn }} {{ $on }}">Bring the power back</button>
                @endif
                @if ($sim['roof_sensor_stuck'])
                    <button wire:click="send('roof_sensor_stuck', false)" class="{{ $btn }} {{ $on }}">Fix the roof sensor</button>
                @else
                    <button wire:click="send('roof_sensor_stuck', true)" class="{{ $btn }}">Jam the roof sensor</button>
                @endif
                <button wire:click="send('underground_level', 8)" class="{{ $btn }}">Nearly empty the underground tank</button>
            </div>
            <p class="mt-2 text-xs text-muted">A jammed sensor keeps showing the same level, so the pump keeps running until the longest-run limit stops it.</p>
        </section>

        <section class="rounded-xl border border-line bg-card p-4">
            <h2 class="font-semibold">Jump to a level</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                <button wire:click="send('roof_level', 20)" class="{{ $btn }}">Roof to 20%</button>
                <button wire:click="send('roof_level', 90)" class="{{ $btn }}">Roof to 90%</button>
                <button wire:click="send('underground_level', 100)" class="{{ $btn }}">Fill underground</button>
            </div>
        </section>
    @endif
</div>
