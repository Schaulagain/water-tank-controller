{{-- One tank: the level as a big number and a filled bar, with the levels that matter marked on it. --}}
<section class="rounded-xl border border-line bg-card p-4">
    <div class="flex items-center gap-2 text-sm font-medium">
        <span class="h-2.5 w-2.5 rounded-full {{ $swatch }}"></span>
        {{ $name }}
    </div>

    <p class="mt-2 text-3xl font-semibold tabular-nums">
        {{ $level === null ? '–' : round($level).'%' }}
    </p>
    <p class="h-5 text-sm text-muted">{{ $note }}</p>

    <div class="relative mt-3 h-36 overflow-hidden rounded-lg border border-line bg-page" role="img"
         aria-label="{{ $name }} {{ $level === null ? 'level unknown' : round($level).' percent full' }}">
        @if ($level !== null)
            <div class="absolute inset-x-0 bottom-0 {{ $fill }} transition-[height] duration-700" style="height: {{ max(0, min(100, $level)) }}%"></div>
        @endif
        @foreach ($marks as $mark => $label)
            <div class="absolute inset-x-0 border-t border-dashed border-ink/40" style="bottom: {{ $mark }}%">
                <span class="absolute right-1 -translate-y-full rounded bg-card/80 px-1 text-[11px] leading-4 text-muted">{{ $label }}</span>
            </div>
        @endforeach
    </div>
</section>
