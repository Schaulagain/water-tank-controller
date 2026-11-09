@php
    $at = fn (?int $ts) => $ts ? \Illuminate\Support\Carbon::createFromTimestamp($ts, config('app.timezone')) : null;
    $now = now()->getTimestamp();
    $manual = $state['mode'] === 'manual';
    $running = $online && $state['pump'];
    $waitingUntil = $state['power_wait_until'] && $state['power_wait_until'] > $now ? $at($state['power_wait_until']) : null;
    $active = $manual ? ($state['manual_pump'] ? 'on' : 'off') : 'auto';
    $modes = ['auto' => ['Auto', 'auto'], 'on' => ['On', 'play'], 'off' => ['Off', 'stop']];
    $shown = $pending && $pending !== 'reset' ? $pending : $active;
    $shownIndex = array_search($shown, array_keys($modes));

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

<div wire:poll.30s class="space-y-3">
    @if ($pending)
        {{-- Check more often while waiting, so a missing answer is noticed. --}}
        <div wire:poll.2s class="hidden"></div>
    @endif

    <header class="flex items-center justify-between pb-1">
        <h1 class="text-2xl font-semibold tracking-tight">Tanks</h1>
        @if ($online)
            <span class="flex items-center gap-2 rounded-full bg-card px-3 py-1.5 text-xs font-medium text-muted ring-1 ring-line/70">
                <span class="relative flex size-2">
                    <span class="halo absolute inset-0 rounded-full bg-good"></span>
                    <span class="relative size-2 rounded-full bg-good"></span>
                </span>
                Live
            </span>
        @else
            <span class="flex items-center gap-1.5 rounded-full bg-card px-3 py-1.5 text-xs font-medium text-warn ring-1 ring-warn/40"
                  title="{{ $state['last_seen'] ? 'Last heard from '.$at($state['last_seen'])->diffForHumans() : 'Not heard from yet' }}">
                <x-icon name="wifi-off" class="size-3.5" />
                Offline
                @if ($state['last_seen'])
                    <span class="font-normal text-muted">· {{ $at($state['last_seen'])->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                @endif
            </span>
        @endif
    </header>

    @if ($state['fault'] === 'max_run')
        <div class="page-enter flex items-center gap-3 rounded-3xl bg-bad/10 p-3 pl-4 ring-1 ring-bad/30" role="alert">
            <x-icon name="alert" class="size-5 text-bad" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">Safety stop</p>
                <p class="text-xs text-muted">Hit the {{ $settings['max_run_minutes'] }} min run limit without filling</p>
            </div>
            <button wire:click="command('reset')" @disabled(! $online || $pending)
                    wire:loading.attr="disabled" wire:target="command"
                    class="press group grid size-10 place-items-center rounded-full bg-bad text-white disabled:opacity-60" aria-label="Reset">
                @if ($pending === 'reset')
                    <x-spinner class="size-[18px]" />
                @else
                    <x-icon name="reset" class="size-[18px] group-data-loading:hidden" />
                    <x-spinner class="hidden size-[18px] group-data-loading:block" />
                @endif
            </button>
        </div>
    @endif

    @if ($error)
        <p class="page-enter flex items-center gap-2 rounded-2xl bg-card px-4 py-3 text-sm ring-1 ring-bad/30" role="alert">
            <x-icon name="alert" class="size-4 text-bad" />
            {{ $error }}
        </p>
    @endif

    <div class="grid grid-cols-2 gap-3">
        @include('livewire.partials.tank', [
            'name' => 'Roof',
            'icon' => 'home',
            'level' => $state['roof'],
            'water' => 'bg-roof/75',
            'surface' => 'text-roof/75',
            'active' => $running,
            'flag' => $state['roof'] === null && $online ? 'Sensor not reading' : null,
            'marks' => [$settings['roof_off'], $settings['roof_on']],
        ])
        @include('livewire.partials.tank', [
            'name' => 'Underground',
            'icon' => 'underground',
            'level' => $state['underground'],
            'water' => 'bg-underground/75',
            'surface' => 'text-underground/75',
            'active' => false,
            'flag' => $state['dry_blocked'] ? 'Dry-run guard on' : null,
            'marks' => [$settings['underground_block']],
        ])
    </div>

    <section class="rounded-3xl border border-line/70 bg-card p-4">
        <div class="flex items-center gap-3.5">
            <span class="relative grid size-12 shrink-0 place-items-center">
                @if ($running)
                    <span class="halo absolute inset-0 rounded-full bg-good/40"></span>
                @endif
                <span @class([
                    'relative grid size-12 place-items-center rounded-full transition-colors duration-500',
                    'bg-good text-white' => $running,
                    'bg-page text-muted' => ! $running,
                ])>
                    <x-icon name="power" class="size-5" />
                </span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">
                    @if (! $online)
                        Unknown
                    @elseif ($state['pump'])
                        Running
                        @if ($state['pump_since'])
                            <span class="font-normal tabular-nums text-muted">· {{ max(0, intdiv($now - $state['pump_since'], 60)) }} min</span>
                        @endif
                    @else
                        Idle
                    @endif
                </p>
                @if ($online && $state['reason'])
                    <p class="truncate text-sm text-muted">
                        {{ ucfirst($state['reason']) }}@if ($waitingUntil) · {{ $waitingUntil->format('g:i') }}@endif
                    </p>
                @endif
            </div>
            @if ($manual && $state['manual_until'])
                <span class="flex shrink-0 items-center gap-1 text-xs tabular-nums text-muted" title="Goes back to auto at {{ $at($state['manual_until'])->format('g:i a') }}">
                    <x-icon name="clock" class="size-3.5" />
                    {{ $at($state['manual_until'])->format('g:i') }}
                </span>
            @endif
        </div>

        <div class="relative mt-4 grid grid-cols-3 rounded-2xl bg-page p-1" role="group" aria-label="Pump mode" aria-busy="{{ $pending ? 'true' : 'false' }}">
            <span x-ref="pill" class="absolute inset-y-1 left-1 w-[calc((100%-0.5rem)/3)] rounded-xl bg-card shadow-sm ring-1 ring-line/70 transition-transform duration-300 ease-[cubic-bezier(0.2,0.8,0.2,1)]"
                  style="transform: translateX({{ $shownIndex * 100 }}%)"></span>
            @foreach ($modes as $action => [$label, $icon])
                <button wire:click="command('{{ $action }}')"
                        x-on:click="$refs.pill.style.transform = 'translateX({{ $loop->index * 100 }}%)'"
                        @disabled(! $online || $pending)
                        wire:loading.attr="disabled" wire:target="command"
                        aria-pressed="{{ $shown === $action ? 'true' : 'false' }}"
                        @class([
                            'press group relative z-10 flex items-center justify-center gap-1.5 rounded-xl py-2.5 text-sm font-medium disabled:cursor-not-allowed',
                            'text-ink' => $shown === $action,
                            'text-muted hover:text-ink disabled:opacity-40' => $shown !== $action,
                        ])>
                    @if ($pending === $action)
                        <x-spinner class="size-4" />
                    @else
                        <x-icon :name="$icon" class="size-4 group-data-loading:hidden" />
                        <x-spinner class="hidden size-4 group-data-loading:block" />
                    @endif
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-line/70 bg-card p-4">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold">24 hours</h2>
            <ul class="flex gap-3 text-[11px] text-muted">
                <li class="flex items-center gap-1"><span class="size-2 rounded-full bg-roof"></span>Roof</li>
                <li class="flex items-center gap-1"><span class="size-2 rounded-full bg-underground"></span>Underground</li>
                <li class="flex items-center gap-1"><span class="size-2 rounded-sm bg-band ring-1 ring-line"></span>Pump</li>
            </ul>
        </div>

        @if ($chart->isEmpty())
            <div class="grid place-items-center gap-2 py-10 text-muted">
                <x-icon name="activity" class="size-6 opacity-50" />
                <p class="text-xs">No readings yet</p>
            </div>
        @else
            <div class="mt-4 grid grid-cols-[2rem_1fr_3rem] gap-x-1"
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
                <div class="relative h-40 text-[10px] tabular-nums text-muted" aria-hidden="true">
                    <span class="absolute right-0 top-0 -translate-y-1/2">100</span>
                    <span class="absolute right-0 top-1/2 -translate-y-1/2">50</span>
                    <span class="absolute right-0 bottom-0 translate-y-1/2">0</span>
                </div>

                <div class="relative h-40 touch-pan-y" x-ref="plot"
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
                            <div class="pointer-events-none absolute inset-y-0 w-px bg-muted/50" :style="`left: ${left}%`"></div>
                            <template x-for="tank in ['roof', 'underground']">
                                <span x-show="p[tank] !== null"
                                      class="pointer-events-none absolute size-2.5 -translate-x-1/2 translate-y-1/2 rounded-full ring-2 ring-card"
                                      :class="tank === 'roof' ? 'bg-roof' : 'bg-underground'"
                                      :style="`left: ${left}%; bottom: ${p[tank]}%`"></span>
                            </template>
                            <div class="pointer-events-none absolute -top-1 z-10 w-36 rounded-xl bg-card/95 p-2.5 text-xs shadow-lg ring-1 ring-line backdrop-blur"
                                 :style="left > 55 ? `right: ${100 - left}%; margin-right: 10px` : `left: ${left}%; margin-left: 10px`">
                                <p class="font-medium" x-text="p.time"></p>
                                <p class="mt-1.5 flex justify-between"><span class="flex items-center gap-1.5 text-muted"><span class="size-2 rounded-full bg-roof"></span>Roof</span><span class="tabular-nums" x-text="p.roof === null ? '–' : Math.round(p.roof) + '%'"></span></p>
                                <p class="flex justify-between"><span class="flex items-center gap-1.5 text-muted"><span class="size-2 rounded-full bg-underground"></span>Underground</span><span class="tabular-nums" x-text="p.underground === null ? '–' : Math.round(p.underground) + '%'"></span></p>
                                <p x-show="p.pump" class="mt-1.5 flex items-center gap-1 text-muted"><span class="size-2 rounded-sm bg-band ring-1 ring-line"></span>Pump ran</p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="relative h-40 text-[10px] font-medium" aria-hidden="true">
                    @foreach ($labelAt as $tank => $level)
                        <span class="absolute left-1 translate-y-1/2 whitespace-nowrap tabular-nums" style="bottom: {{ $level }}%">
                            <span @class(['mr-0.5 inline-block size-1.5 rounded-full align-middle', 'bg-roof' => $tank === 'roof', 'bg-underground' => $tank !== 'roof'])></span>{{ round($ends[$tank]) }}%
                        </span>
                    @endforeach
                </div>

                <div></div>
                <div class="mt-1.5 flex justify-between text-[10px] text-muted" aria-hidden="true">
                    @foreach ([24, 18, 12, 6] as $hoursAgo)
                        <span>{{ now()->subHours($hoursAgo)->format('ga') }}</span>
                    @endforeach
                    <span>now</span>
                </div>
            </div>

            <details class="group mt-3 text-xs">
                <summary class="flex cursor-pointer list-none items-center gap-1 text-muted [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-down" class="size-3.5 transition-transform group-open:rotate-180" />
                    Table
                </summary>
                <table class="mt-2 w-full text-left tabular-nums">
                    <thead class="text-muted">
                        <tr><th class="py-1 font-medium">Hour</th><th class="font-medium">Roof</th><th class="font-medium">Underground</th><th class="font-medium">Pump</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($chart->hourly() as $row)
                            <tr class="border-t border-line/70">
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

    <section class="rounded-3xl border border-line/70 bg-card p-4">
        <h2 class="flex items-center gap-1.5 text-sm font-semibold">
            <x-icon name="history" class="size-4 text-muted" />
            Runs
        </h2>
        <ul class="mt-2">
            @forelse ($runs as $run)
                <li class="flex items-center gap-3 border-t border-line/60 py-2.5 first:border-t-0">
                    <span @class(['grid size-8 shrink-0 place-items-center rounded-full', 'bg-good/15 text-good' => ! $run->ended_at, 'bg-page text-muted' => $run->ended_at])>
                        @if ($run->ended_at)
                            <x-icon name="check" class="size-4" />
                        @else
                            <x-spinner class="size-4" />
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            {{ $run->started_at->isToday() ? 'Today' : ($run->started_at->isYesterday() ? 'Yesterday' : $run->started_at->format('j M')) }}
                            <span class="text-muted">{{ $run->started_at->format('g:i a') }}</span>
                        </p>
                        <p class="truncate text-xs text-muted">{{ ucfirst($run->start_reason ?? 'started') }} → {{ $run->stop_reason ?? 'running' }}</p>
                    </div>
                    <p class="shrink-0 text-sm tabular-nums text-muted">{{ $run->ended_at ? $run->minutes().' min' : '' }}</p>
                </li>
            @empty
                <li class="py-6 text-center text-xs text-muted">No runs yet</li>
            @endforelse
        </ul>
    </section>
</div>
