<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
        <meta name="reverb-port" content="{{ config('device.reverb_public_port') }}">
    @endauth
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Water">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#f4f4f2" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#121211" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ $title ?? 'Water' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh font-sans text-ink antialiased selection:bg-roof/20">
    <main @class(['page-enter mx-auto max-w-lg px-4 pt-[max(1.25rem,env(safe-area-inset-top))]', 'pb-28' => auth()->check(), 'pb-10' => ! auth()->check()])>
        {{ $slot }}
    </main>

    @auth
        @php($tabs = ['dashboard' => ['Tanks', 'droplet'], 'settings' => ['Settings', 'sliders']] + (config('device.simulator') ? ['simulator' => ['Simulator', 'flask']] : []))
        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line/70 bg-card/80 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl">
            <div class="mx-auto flex max-w-lg items-stretch justify-around px-2">
                @foreach ($tabs as $route => [$label, $icon])
                    @php($here = request()->routeIs($route))
                    <a href="{{ route($route) }}" wire:navigate aria-current="{{ $here ? 'page' : 'false' }}"
                       @class(['press flex flex-1 flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium', 'text-ink' => $here, 'text-muted hover:text-ink' => ! $here])>
                        <span @class(['grid h-7 w-12 place-items-center rounded-full transition-colors', 'bg-band' => $here])>
                            <x-icon :name="$icon" class="size-[18px]" />
                        </span>
                        {{ $label }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="flex flex-1">
                    @csrf
                    <button class="press flex flex-1 flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium text-muted hover:text-ink">
                        <span class="grid h-7 w-12 place-items-center"><x-icon name="log-out" class="size-[18px]" /></span>
                        Log out
                    </button>
                </form>
            </div>
        </nav>
    @endauth
</body>
</html>
