<div class="mx-auto mt-16 max-w-sm">
    <h1 class="text-2xl font-semibold">Water tanks</h1>
    <p class="mt-1 text-sm text-muted">Log in to see the tanks and run the pump.</p>

    <form wire:submit="login" class="mt-6 space-y-4 rounded-xl border border-line bg-card p-5">
        <label class="block">
            <span class="text-sm font-medium">Email</span>
            <input type="email" wire:model="email" autocomplete="username" autofocus required
                   class="mt-1 w-full rounded-lg border border-line bg-page px-3 py-2.5 text-base outline-none focus:border-ink">
        </label>
        <label class="block">
            <span class="text-sm font-medium">Password</span>
            <input type="password" wire:model="password" autocomplete="current-password" required
                   class="mt-1 w-full rounded-lg border border-line bg-page px-3 py-2.5 text-base outline-none focus:border-ink">
        </label>

        @error('email') <p class="text-sm text-bad">{{ $message }}</p> @enderror

        <button class="w-full rounded-lg bg-accent px-4 py-2.5 font-medium text-page disabled:opacity-60" wire:loading.attr="disabled">
            Log in
        </button>
    </form>
</div>
