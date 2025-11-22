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

    // 委託販売請求書発行
    Volt::route('dashboard/consignment-sales', 'consignment-sales.index')->name('consignment-sales.index');
    Volt::route('dashboard/consignment-sales/settlement', 'consignment-sales.settlement')->name('consignment-sales.settlement');

    // 全銀フォーマット変換
    Volt::route('dashboard/bank-transfers', 'bank-transfers.index')->name('bank-transfers.index');

    // 全銀フォーマットファイルダウンロード
    Route::get('dashboard/bank-transfers/download/{file}', function (string $file) {
        $path = 'temp/' . $file;
        $fullPath = storage_path('app/private/' . $path);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        // ファイル拡張子に応じてContent-Typeを設定
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeType = match ($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };

        $downloadName = 'zenkin_format_' . now()->format('YmdHis') . '.' . $extension;

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => $mimeType,
        ])->deleteFileAfterSend();
    })->name('bank-transfers.download');
});
