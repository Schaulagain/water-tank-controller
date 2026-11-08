<?php

use App\Livewire\Dashboard;
use App\Livewire\Login;
use App\Livewire\SettingsPage;
use App\Livewire\Simulator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', Login::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/settings', SettingsPage::class)->name('settings');
    Route::livewire('/simulator', Simulator::class)->name('simulator');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
