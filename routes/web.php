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
    Volt::route('dashboard/consignment-sales/settlement/{batch?}', 'consignment-sales.settlement')->name('consignment-sales.settlement');
    Route::get('dashboard/consignment-sales/settlement/export', [\App\Http\Controllers\ConsignmentSettlementExportController::class, 'download'])
        ->name('consignment-sales.settlement.export');
    Volt::route('dashboard/consignment-sales/settlement', 'consignment-sales.settlement')->name('consignment-sales.settlement');

    // POSデータ集計
    Volt::route('dashboard/sales', 'sales.index')->name('sales.index');
    Volt::route('dashboard/sales/aggregate', 'sales.aggregate')->name('sales.aggregate');
    Route::post('dashboard/sales/export', [App\Http\Controllers\SalesController::class, 'aggregateAndExport'])->name('sales.export');
    Volt::route('dashboard/sales/history', 'sales.history')->name('sales.history');
    Volt::route('dashboard/sales/statistics', 'sales.statistics')->name('sales.statistics');

    // 履歴からのダウンロード
    Route::get('dashboard/sales/history/{aggregation}/download', function (App\Models\SalesAggregation $aggregation) {
        if (! $aggregation->fileExists()) {
            abort(404, 'ファイルが見つかりません');
        }

        $fullPath = $aggregation->full_path;

        // ファイル拡張子に応じてContent-Typeを設定
        $extension = pathinfo($aggregation->excel_filename, PATHINFO_EXTENSION);
        $mimeType = match ($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };

        return response()->download($fullPath, $aggregation->excel_filename, [
            'Content-Type' => $mimeType,
        ]);
    })->name('sales.download-history');
    // 全銀フォーマット変換
    Volt::route('dashboard/bank-transfers', 'bank-transfers.index')->name('bank-transfers.index'); // トップページ
    Volt::route('dashboard/bank-transfers/convert', 'bank-transfers.convert')->name('bank-transfers.convert'); // 変換ページ
    Volt::route('dashboard/bank-transfers/history', 'bank-transfers.history')->name('bank-transfers.history'); // 履歴一覧

    // 全銀フォーマットファイルダウンロード（一時ファイル用 - 後方互換性のため残す）
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

    // 履歴からのダウンロード
    Route::get('dashboard/bank-transfers/history/{conversion}/download', function (App\Models\BankTransferConversion $conversion) {
        if (! $conversion->fileExists()) {
            abort(404, 'ファイルが見つかりません');
        }

        $fullPath = $conversion->full_path;

        // ファイル拡張子に応じてContent-Typeを設定
        $extension = pathinfo($conversion->converted_filename, PATHINFO_EXTENSION);
        $mimeType = match ($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };

        return response()->download($fullPath, $conversion->converted_filename, [
            'Content-Type' => $mimeType,
        ]);
    })->name('bank-transfers.download-history');
});
