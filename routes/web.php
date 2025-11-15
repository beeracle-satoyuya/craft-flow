<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // 設定ページ
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // 予約管理
    Volt::route('dashboard/reservations', 'reservations.index')->name('reservations.index');
    Volt::route('dashboard/reservations/list', 'reservations.list')->name('reservations.list');
    Volt::route('dashboard/reservations/calendar', 'reservations.calendar')->name('reservations.calendar');
    Volt::route('dashboard/reservations/statistics', 'reservations.statistics')->name('reservations.statistics');
    Volt::route('dashboard/reservations/create', 'reservations.create')->name('reservations.create');
    Volt::route('dashboard/reservations/{reservation}', 'reservations.show')->name('reservations.show');
    Volt::route('dashboard/reservations/{reservation}/edit', 'reservations.edit')->name('reservations.edit');

    // 体験プログラム管理
    Volt::route('dashboard/workshops', 'workshops.index')->name('workshops.index');
    Volt::route('dashboard/workshops/create', 'workshops.create')->name('workshops.create');
    Volt::route('dashboard/workshops/{workshop}', 'workshops.show')->name('workshops.show');
    Volt::route('dashboard/workshops/{workshop}/edit', 'workshops.edit')->name('workshops.edit');

    // プログラムカテゴリ管理
    Volt::route('dashboard/workshop-categories', 'workshop-categories.index')->name('workshop-categories.index');
});
