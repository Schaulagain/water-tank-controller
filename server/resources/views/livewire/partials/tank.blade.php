{{-- One tank: the level as a number and as water in a little tank, with the levels that matter ticked on the side. --}}
@php($pct = $level === null ? null : max(0, min(100, $level)))
<section class="rounded-3xl border border-line/70 bg-card p-4">
    <div class="flex items-center justify-between text-muted">
        <span class="flex items-center gap-1.5 text-xs font-medium">
            <x-icon :name="$icon" class="size-3.5" />
            {{ $name }}
        </span>
        @if ($flag)
            <span class="text-warn" title="{{ $flag }}"><x-icon name="alert" class="size-4" /></span>
        @endif
    </div>

    <p class="mt-1 text-[2rem] font-semibold leading-tight tracking-tight tabular-nums">
        {{ $pct === null ? '–' : round($pct) }}<span class="text-base font-medium text-muted">%</span>
    </p>

    <div class="relative mt-3 h-32 overflow-hidden rounded-2xl bg-page" role="img"
         aria-label="{{ $name }} {{ $pct === null ? 'level unknown' : round($pct).' percent full' }}{{ $flag ? ', '.$flag : '' }}">
        @if ($pct !== null)
            <div class="absolute inset-x-0 bottom-0 {{ $water }} transition-[height] duration-1000 ease-[cubic-bezier(0.2,0.8,0.2,1)]" style="height: {{ $pct }}%">
                {{-- the surface --}}
                <svg class="{{ $surface }} wave {{ $active ? 'wave-fast' : '' }} absolute -top-1.5 left-0 h-1.5 w-[200%]" viewBox="0 0 200 6" preserveAspectRatio="none" aria-hidden="true">
                    <path fill="currentColor" d="M0 3 Q 12.5 0 25 3 T 50 3 T 75 3 T 100 3 T 125 3 T 150 3 T 175 3 T 200 3 V 6 H 0 Z" />
                </svg>
                @if ($active)
                    @foreach ([[22, 0], [50, 0.8], [74, 1.6]] as [$x, $delay])
                        <span class="bubble absolute bottom-1 size-1.5 rounded-full bg-white/70" style="left: {{ $x }}%; animation-delay: {{ $delay }}s"></span>
                    @endforeach
                @endif
            </div>
        @endif

        @foreach ($marks as $mark)
            <span class="absolute right-0 h-px w-2.5 bg-ink/30" style="bottom: {{ $mark }}%"></span>
            <span class="absolute right-3.5 translate-y-1/2 text-[10px] tabular-nums text-ink/45" style="bottom: {{ $mark }}%">{{ $mark }}</span>
        @endforeach
    </div>
</section>
