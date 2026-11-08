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
    <meta name="theme-color" content="#f4f4f2" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#121211" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ $title ?? 'Water' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-ink antialiased">
    @auth
        <nav class="border-b border-line bg-card">
            <div class="mx-auto flex max-w-3xl items-center gap-1 px-4 py-2 text-sm">
                <a href="{{ route('dashboard') }}" wire:navigate class="mr-auto py-2 font-semibold">Water</a>
                @php($links = ['dashboard' => 'Tanks', 'settings' => 'Settings'] + (config('device.simulator') ? ['simulator' => 'Simulator'] : []))
                @foreach ($links as $route => $label)
                    <a href="{{ route($route) }}" wire:navigate
                       @class(['rounded-md px-3 py-2', 'bg-band font-medium' => request()->routeIs($route), 'text-muted hover:text-ink' => ! request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md px-3 py-2 text-muted hover:text-ink">Log out</button>
                </form>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-3xl px-4 pb-16 pt-5">
        {{ $slot }}
    </main>
</body>
</html>
