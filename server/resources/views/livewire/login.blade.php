<div class="mx-auto mt-[18vh] max-w-xs">
    <div class="grid size-14 place-items-center rounded-2xl bg-roof text-white shadow-lg shadow-roof/25">
        <x-icon name="droplet" class="size-7" />
    </div>
    <h1 class="mt-5 text-2xl font-semibold tracking-tight">Water</h1>

    <form wire:submit="login" class="mt-6 space-y-3">
        <input type="email" wire:model="email" autocomplete="username" autofocus required placeholder="Email" aria-label="Email"
               class="w-full rounded-2xl border border-line/70 bg-card px-4 py-3 text-base outline-none transition focus:border-ink/40 focus:ring-4 focus:ring-ink/5">
        <input type="password" wire:model="password" autocomplete="current-password" required placeholder="Password" aria-label="Password"
               class="w-full rounded-2xl border border-line/70 bg-card px-4 py-3 text-base outline-none transition focus:border-ink/40 focus:ring-4 focus:ring-ink/5">

        @error('email')
            <p class="page-enter flex items-center gap-1.5 px-1 text-sm text-bad"><x-icon name="alert" class="size-4" />{{ $message }}</p>
        @enderror

        <button class="press grid h-12 w-full place-items-center rounded-2xl bg-accent font-medium text-page disabled:opacity-70" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Log in</span>
            <x-spinner wire:loading wire:target="login" class="size-5" />
        </button>
    </form>
</div>
