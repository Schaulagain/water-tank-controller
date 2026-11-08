@php
    $at = fn (?int $ts) => $ts ? \Illuminate\Support\Carbon::createFromTimestamp($ts, config('app.timezone')) : null;
    $now = now()->getTimestamp();
    $manual = $state['mode'] === 'manual';
    $waitingUntil = $state['power_wait_until'] && $state['power_wait_until'] > $now ? $at($state['power_wait_until']) : null;
    $active = $manual ? ($state['pump'] ? 'on' : 'off') : 'auto';

    // Labels at the end of each chart line, nudged apart so they don't overlap.
    $ends = array_filter(['roof' => $chart->latest('roof'), 'underground' => $chart->latest('underground')], fn ($v) => $v !== null);
    if (count($ends) === 2 && abs($ends['roof'] - $ends['underground']) < 12) {
        $high = $ends['roof'] >= $ends['underground'] ? 'roof' : 'underground';
        $low = $high === 'roof' ? 'underground' : 'roof';
        $mid = ($ends['roof'] + $ends['underground']) / 2;
        $labelAt = [$high => min(100, $mid + 6), $low => max(0, $mid - 6)];
    } else {
        $labelAt = $ends;
    }
@endphp

<div wire:poll.30s class="space-y-4">
    @if (! $online)
        <div class="flex gap-3 rounded-xl border border-warn/50 bg-card p-4" role="status">
            <span aria-hidden="true" class="text-warn">⚠</span>
            <div>
                <p class="font-medium">Can't reach the controller</p>
                <p class="text-sm text-muted">
                    @if ($state['last_seen'])
                        Last heard from {{ $at($state['last_seen'])->diffForHumans() }}. It could be a power cut or a WiFi problem, so the levels below may be out of date.
                    @else
                        It hasn't reported in yet.
                    @endif
                </p>
            </div>
        </div>
    @endif

    @if ($state['fault'] === 'max_run')
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-bad/50 bg-card p-4" role="alert">
            <span aria-hidden="true" class="text-bad">⛔</span>
            <div class="min-w-0 flex-1">
                <p class="font-medium">Pump stopped for safety</p>
                <p class="text-sm text-muted">It ran for {{ $settings['max_run_minutes'] }} minutes without filling the roof tank. Check for a leak or a stuck sensor, then reset it.</p>
            </div>
            <button wire:click="command('reset')" @disabled(! $online)
                    class="rounded-lg bg-accent px-4 py-2 text-sm font-medium text-page disabled:opacity-50">Reset</button>
        </div>
    @endif

    @if ($error)
        <p class="rounded-xl border border-bad/50 bg-card p-4 text-sm" role="alert">{{ $error }}</p>
    @endif

    <div class="grid grid-cols-2 gap-3">
        @include('livewire.partials.tank', [
            'name' => 'Roof tank',
            'level' => $state['roof'],
            'swatch' => 'bg-roof',
            'fill' => 'bg-roof/70',
            'note' => $state['roof'] === null && $online ? 'Sensor not reading' : '',
            'marks' => [$settings['roof_off'] => 'stop', $settings['roof_on'] => 'start'],
        ])
        @include('livewire.partials.tank', [
            'name' => 'Underground tank',
            'level' => $state['underground'],
            'swatch' => 'bg-underground',
            'fill' => 'bg-underground/70',
            'note' => $state['dry_blocked'] ? 'Too low to pump' : '',
            'marks' => [$settings['underground_block'] => 'too low'],
        ])
    </div>

    <section class="rounded-xl border border-line bg-card p-4">
        <div class="flex items-start gap-3">
            <span @class([
                'mt-1.5 h-3 w-3 shrink-0 rounded-full',
                'bg-good' => $online && $state['pump'],
                'bg-line' => ! $online || ! $state['pump'],
            ])></span>
            <div class="min-w-0 flex-1">
                <p class="text-lg font-semibold">
                    @if (! $online)
                        Pump status unknown
                    @elseif ($state['pump'])
                        Pump is running
                        @if ($state['pump_since'])
                            <span class="font-normal text-muted">· {{ max(0, intdiv($now - $state['pump_since'], 60)) }} min</span>
                        @endif
                    @else
                        Pump is off
                    @endif
                </p>
                @if ($online && $state['reason'])
                    <p class="text-sm text-muted">{{ ucfirst($state['reason']) }}@if ($waitingUntil), until {{ $waitingUntil->format('g:i a') }}@endif</p>
                @endif
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-1 rounded-lg bg-page p-1" role="group" aria-label="Pump mode">
            @foreach (['auto' => 'Auto', 'on' => 'On', 'off' => 'Off'] as $action => $label)
                <button wire:click="command('{{ $action }}')" @disabled(! $online) aria-pressed="{{ $active === $action ? 'true' : 'false' }}"
                        @class([
                            'rounded-md py-2.5 text-sm font-medium transition disabled:opacity-50',
                            'bg-card shadow-sm ring-1 ring-line' => $active === $action,
                            'text-muted hover:text-ink' => $active !== $action,
                        ])>{{ $label }}</button>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-muted">
            @if ($manual && $state['manual_until'])
                Switched by hand. Goes back to auto at {{ $at($state['manual_until'])->format('g:i a') }}.
            @else
                Auto fills the roof tank when it drops to {{ $settings['roof_on'] }}% and stops at {{ $settings['roof_off'] }}%.
            @endif
            The pump never runs while the underground tank is too low.
        </p>
    </section>

    <section class="rounded-xl border border-line bg-card p-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold">Last 24 hours</h2>
            <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                <li class="flex items-center gap-1.5"><span class="h-0.5 w-4 rounded bg-roof"></span>Roof tank</li>
                <li class="flex items-center gap-1.5"><span class="h-0.5 w-4 rounded bg-underground"></span>Underground tank</li>
                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-band ring-1 ring-line"></span>Pump running</li>
            </ul>
        </div>

        @if ($chart->isEmpty())
            <p class="mt-6 mb-4 text-sm text-muted">No readings yet. They'll show up here once the controller starts reporting.</p>
        @else
            <div class="mt-4 grid grid-cols-[2.25rem_1fr_3.25rem] gap-x-1"
                 wire:key="chart-{{ md5(json_encode($chart->forTooltip())) }}"
                 x-data="{
                     points: @js($chart->forTooltip()),
                     i: null,
                     move(e) {
                         let r = this.$refs.plot.getBoundingClientRect()
                         let x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left
                         this.i = Math.max(0, Math.min(this.points.length - 1, Math.floor(x / r.width * this.points.length)))
                     },
                     get p() { return this.i === null ? null : this.points[this.i] },
                     get left() { return (this.i + 0.5) / this.points.length * 100 },
                 }">
                <div class="relative h-48 text-[11px] tabular-nums text-muted" aria-hidden="true">
                    <span class="absolute right-0 top-0 -translate-y-1/2">100%</span>
                    <span class="absolute right-0 top-1/2 -translate-y-1/2">50%</span>
                    <span class="absolute right-0 bottom-0 translate-y-1/2">0%</span>
                </div>

                <div class="relative h-48 touch-pan-y" x-ref="plot"
                     @mousemove="move($event)" @mouseleave="i = null"
                     @touchstart.passive="move($event)" @touchmove.passive="move($event)" @touchend="i = null">
                    <svg viewBox="0 0 {{ $chart::WIDTH }} {{ $chart::HEIGHT }}" preserveAspectRatio="none"
                         class="absolute inset-0 h-full w-full overflow-visible" role="img"
                         aria-label="Tank levels over the last 24 hours. Roof tank now {{ round($chart->latest('roof') ?? 0) }}%, underground tank now {{ round($chart->latest('underground') ?? 0) }}%.">
                        @foreach ($chart->pumpBands() as $band)
                            <rect x="{{ $band['x'] }}" y="0" width="{{ $band['width'] }}" height="{{ $chart::HEIGHT }}" class="fill-band" />
                        @endforeach
                        @foreach ([0, 50, 100] as $grid)
                            <line x1="0" x2="{{ $chart::WIDTH }}" y1="{{ $chart->y($grid) }}" y2="{{ $chart->y($grid) }}"
                                  class="stroke-line" stroke-width="1" vector-effect="non-scaling-stroke" />
                        @endforeach
                        <path d="{{ $chart->path('underground') }}" fill="none" class="stroke-underground" stroke-width="2"
                              stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        <path d="{{ $chart->path('roof') }}" fill="none" class="stroke-roof" stroke-width="2"
                              stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                    </svg>

                    <template x-if="p">
                        <div>
                            <div class="pointer-events-none absolute inset-y-0 w-px bg-muted/60" :style="`left: ${left}%`"></div>
                            <template x-for="tank in ['roof', 'underground']">
                                <span x-show="p[tank] !== null"
                                      class="pointer-events-none absolute h-2.5 w-2.5 -translate-x-1/2 translate-y-1/2 rounded-full ring-2 ring-card"
                                      :class="tank === 'roof' ? 'bg-roof' : 'bg-underground'"
                                      :style="`left: ${left}%; bottom: ${p[tank]}%`"></span>
                            </template>
                            <div class="pointer-events-none absolute top-0 z-10 w-40 rounded-lg border border-line bg-card p-2 text-xs shadow-lg"
                                 :style="left > 55 ? `right: ${100 - left}%; margin-right: 8px` : `left: ${left}%; margin-left: 8px`">
                                <p class="font-medium" x-text="p.time"></p>
                                <p class="mt-1 flex justify-between gap-2"><span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded bg-roof"></span>Roof</span><span class="tabular-nums" x-text="p.roof === null ? '–' : Math.round(p.roof) + '%'"></span></p>
                                <p class="flex justify-between gap-2"><span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded bg-underground"></span>Underground</span><span class="tabular-nums" x-text="p.underground === null ? '–' : Math.round(p.underground) + '%'"></span></p>
                                <p class="mt-1 text-muted" x-text="p.pump ? 'Pump ran' : 'Pump off'"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="relative h-48 text-[11px] font-medium" aria-hidden="true">
                    @foreach ($labelAt as $tank => $level)
                        <span class="absolute left-1 translate-y-1/2 whitespace-nowrap tabular-nums" style="bottom: {{ $level }}%">
                            {{ $tank === 'roof' ? 'Roof' : 'Under' }} {{ round($ends[$tank]) }}%
                        </span>
                    @endforeach
                </div>

                <div></div>
                <div class="mt-1.5 flex justify-between text-[11px] text-muted" aria-hidden="true">
                    @foreach ([24, 18, 12, 6] as $hoursAgo)
                        <span>{{ now()->subHours($hoursAgo)->format('g a') }}</span>
                    @endforeach
                    <span>now</span>
                </div>
            </div>

            <details class="mt-4 text-sm">
                <summary class="cursor-pointer text-muted">Show as a table</summary>
                <table class="mt-2 w-full text-left tabular-nums">
                    <thead class="text-xs text-muted">
                        <tr><th class="py-1 font-medium">Hour</th><th class="font-medium">Roof</th><th class="font-medium">Underground</th><th class="font-medium">Pump</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($chart->hourly() as $row)
                            <tr class="border-t border-line">
                                <td class="py-1">{{ $row['hour'] }}</td>
                                <td>{{ $row['roof'] === null ? '–' : $row['roof'].'%' }}</td>
                                <td>{{ $row['underground'] === null ? '–' : $row['underground'].'%' }}</td>
                                <td>{{ $row['pump'] ? 'ran' : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @endif
    </section>

    <section class="rounded-xl border border-line bg-card p-4">
        <h2 class="font-semibold">Recent pump runs</h2>
        @forelse ($runs as $run)
            <div class="flex items-baseline justify-between gap-3 border-t border-line py-2.5 text-sm first-of-type:mt-2">
                <div class="min-w-0">
                    <p>{{ $run->started_at->isToday() ? 'Today' : ($run->started_at->isYesterday() ? 'Yesterday' : $run->started_at->format('j M')) }}, {{ $run->started_at->format('g:i a') }}</p>
                    <p class="truncate text-muted">{{ ucfirst($run->start_reason ?? 'started') }} → {{ $run->stop_reason ?? 'still running' }}</p>
                </div>
                <p class="shrink-0 tabular-nums text-muted">{{ $run->ended_at ? $run->minutes().' min' : 'running' }}</p>
            </div>
        @empty
            <p class="mt-2 text-sm text-muted">The pump hasn't run yet.</p>
        @endforelse
    </section>
</div>
