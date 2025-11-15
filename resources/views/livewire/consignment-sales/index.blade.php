<?php

use function Livewire\Volt\{state, rules, updated, computed};
use App\Models\ConsignmentSale;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

// 状態管理
state([
    'excelFile' => null,
    'importedData' => null,
    'summary' => null,
    'isUploading' => false,
    'uploadError' => null,
    'startDate' => null,
    'endDate' => null,
]);

// バリデーションルール
rules([
    'excelFile' => 'nullable|file|mimes:xlsx,xls|max:10240', // 10MBまで
    'startDate' => 'nullable|date',
    'endDate' => 'nullable|date|after_or_equal:startDate',
]);

// Excelファイルのインポート処理
$importExcel = function ($file) {
    $this->isUploading = true;
    $this->uploadError = null;

    try {
        // ファイルを一時保存
        $path = $file->store('temp', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // PhpSpreadsheetでExcelファイルを読み込む
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // ヘッダー行をスキップ（1行目を想定）
        $headerRow = array_shift($rows);

        // データをパースして保存
        $importedCount = 0;
        $importedRecords = [];

        foreach ($rows as $row) {
            // 空行をスキップ
            if (empty(array_filter($row))) {
                continue;
            }

            // Excelの列順序を想定: 販売日, 商品名, 数量, 単価, 金額, 手数料, 備考
            $saleDate = $row[0] ?? null;
            $productName = $row[1] ?? null;
            $quantity = $row[2] ?? 1;
            $unitPrice = $row[3] ?? 0;
            $amount = $row[4] ?? 0;
            $commission = $row[5] ?? 0;
            $notes = $row[6] ?? null;

            // 必須項目のチェック
            if (empty($saleDate) || empty($productName)) {
                continue;
            }

            // 日付の変換（Excelの日付形式に対応）
            if (is_numeric($saleDate)) {
                // Excelの日付シリアル値を日付に変換
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($saleDate);
                $saleDate = $excelDate->format('Y-m-d');
            } else {
                // 文字列の場合はそのまま使用（Y-m-d形式を想定）
                $saleDate = date('Y-m-d', strtotime($saleDate));
            }

            // データを保存
            $consignmentSale = ConsignmentSale::create([
                'sale_date' => $saleDate,
                'product_name' => $productName,
                'quantity' => (int) $quantity,
                'unit_price' => (int) $unitPrice,
                'amount' => (int) $amount,
                'commission' => (int) $commission,
                'notes' => $notes,
            ]);

            $importedRecords[] = $consignmentSale;
            $importedCount++;
        }

        // 一時ファイルを削除
        Storage::disk('local')->delete($path);

        // インポートしたデータを表示用に設定
        $this->importedData = collect($importedRecords);

        // 集計サマリーを計算
        $this->summary = [
            'total_count' => $importedCount,
            'total_amount' => $this->importedData->sum('amount'),
            'total_commission' => $this->importedData->sum('commission'),
        ];

        $this->isUploading = false;
    } catch (\Exception $e) {
        Log::error('Excelインポートエラー: ' . $e->getMessage());
        $this->uploadError = 'Excelファイルの読み込みに失敗しました: ' . $e->getMessage();
        $this->isUploading = false;

        // エラー時も一時ファイルを削除
        if (isset($path)) {
            Storage::disk('local')->delete($path);
        }
    }
};

// Excelファイルがアップロードされたときの処理
updated([
    'excelFile' => function ($file) {
        if ($file) {
            $this->importExcel($file);
        }
    },
]);

// 日付検索でデータを取得（computedを使用）
$searchedData = computed(function () {
    return ConsignmentSale::query()
        ->when($this->startDate, function (Builder $query) {
            $query->whereDate('sale_date', '>=', $this->startDate);
        })
        ->when($this->endDate, function (Builder $query) {
            $query->whereDate('sale_date', '<=', $this->endDate);
        })
        ->orderBy('sale_date', 'desc')
        ->get();
});

$searchSummary = computed(function () {
    $data = ConsignmentSale::query()
        ->when($this->startDate, function (Builder $query) {
            $query->whereDate('sale_date', '>=', $this->startDate);
        })
        ->when($this->endDate, function (Builder $query) {
            $query->whereDate('sale_date', '<=', $this->endDate);
        })
        ->get();

    return [
        'total_count' => $data->count(),
        'total_amount' => $data->sum('amount'),
        'total_commission' => $data->sum('commission'),
    ];
});

// 検索条件をクリア
$clearSearch = function () {
    $this->startDate = null;
    $this->endDate = null;
};

// 次の画面へ進む
$next = function () {
    // 精算書発行画面への遷移（後で実装）
    // TODO: 精算書発行画面のルートを作成後に有効化
    // return $this->redirect(route('consignment-sales.settlement'), navigate: true);

    // 一時的にダッシュボードに戻る
    return $this->redirect(route('dashboard'), navigate: true);
};

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                委託販売請求書発行
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                Excelファイルをアップロードして委託販売データを登録するか、日付範囲で検索します
            </p>
        </div>

        <!-- 日付検索 -->
        <div class="mb-8">
            <flux:card>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                        日付範囲で検索
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>開始日</flux:label>
                                <flux:input type="date" wire:model.live="startDate" />
                                <flux:error name="startDate" />
                            </flux:field>
                        </div>
                        <div>
                            <flux:field>
                                <flux:label>終了日</flux:label>
                                <flux:input type="date" wire:model.live="endDate" />
                                <flux:error name="endDate" />
                            </flux:field>
                        </div>
                        <div class="flex items-end gap-2">
                            @if ($startDate || $endDate)
                                <flux:button type="button" variant="ghost" wire:click="clearSearch" class="flex-1">
                                    クリア
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- 検索結果表示 -->
        @if ($startDate || $endDate)
            <div class="mb-8">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            検索結果
                        </h2>

                        @if ($this->searchedData->count() > 0)
                            <!-- サマリー情報 -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        件数
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        {{ number_format($this->searchSummary['total_count']) }}件
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        売上合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($this->searchSummary['total_amount']) }}
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        手数料合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($this->searchSummary['total_commission']) }}
                                    </div>
                                </div>
                            </div>

                            <!-- 一覧テーブル -->
                            <div class="overflow-x-auto">
                                <flux:table>
                                    <flux:columns>
                                        <flux:column>販売日</flux:column>
                                        <flux:column>商品名</flux:column>
                                        <flux:column>数量</flux:column>
                                        <flux:column>単価</flux:column>
                                        <flux:column>金額</flux:column>
                                        <flux:column>手数料</flux:column>
                                        <flux:column>備考</flux:column>
                                    </flux:columns>

                                    <flux:rows>
                                        @foreach ($this->searchedData as $sale)
                                            <flux:row>
                                                <flux:cell>
                                                    {{ $sale->sale_date->format('Y/m/d') }}
                                                </flux:cell>
                                                <flux:cell>
                                                    {{ $sale->product_name }}
                                                </flux:cell>
                                                <flux:cell>
                                                    {{ number_format($sale->quantity) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    ¥{{ number_format($sale->unit_price) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    ¥{{ number_format($sale->amount) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    ¥{{ number_format($sale->commission) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    {{ $sale->notes ?? '-' }}
                                                </flux:cell>
                                            </flux:row>
                                        @endforeach
                                    </flux:rows>
                                </flux:table>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="text-zinc-500 mb-4">
                                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-medium">該当するデータがありません</p>
                                    <p class="mt-1 text-sm text-zinc-400">別の日付範囲で検索してください</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>
        @endif

        <!-- Excelファイルアップロード -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                        Excelファイルアップロード
                    </h2>

                    <!-- ドロップゾーン -->
                    <div x-data="{
                        isDragging: false,
                        handleDrop(e) {
                            e.preventDefault();
                            this.isDragging = false;
                            if (e.dataTransfer.files.length > 0) {
                                @this.upload('excelFile', e.dataTransfer.files[0]);
                            }
                        },
                        handleDragOver(e) {
                            e.preventDefault();
                            this.isDragging = true;
                        },
                        handleDragLeave(e) {
                            e.preventDefault();
                            this.isDragging = false;
                        }
                    }" @drop.prevent="handleDrop" @dragover.prevent="handleDragOver"
                        @dragleave.prevent="handleDragLeave"
                        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' :
                            'border-zinc-300 dark:border-zinc-600'">
                        <div wire:loading.remove wire:target="excelFile">
                            <svg class="mx-auto h-12 w-12 text-zinc-400 mb-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-lg font-medium text-zinc-900 dark:text-white mb-2">
                                Excelファイルをドロップするか、クリックして選択
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                                .xlsx または .xls 形式のファイルをアップロードしてください（最大10MB）
                            </p>
                            <flux:input.file wire:model="excelFile" accept=".xlsx,.xls" class="mx-auto" />
                        </div>
                        <div wire:loading wire:target="excelFile" class="py-8">
                            <div class="flex flex-col items-center">
                                <svg class="animate-spin h-8 w-8 text-primary-500 mb-4"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <p class="text-lg font-medium text-zinc-900 dark:text-white">
                                    アップロード中...
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- エラーメッセージ -->
                    @if ($uploadError)
                        <div class="mt-4">
                            <flux:callout variant="danger">
                                {{ $uploadError }}
                            </flux:callout>
                        </div>
                    @endif

                    @error('excelFile')
                        <div class="mt-4">
                            <flux:error>{{ $message }}</flux:error>
                        </div>
                    @enderror

                    <!-- Excelファイル形式の説明 -->
                    <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">
                            Excelファイルの形式
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                            1行目はヘッダー行として扱われます。2行目以降に以下の形式でデータを入力してください：
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-zinc-100 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">販売日</th>
                                        <th class="px-3 py-2 text-left">商品名</th>
                                        <th class="px-3 py-2 text-left">数量</th>
                                        <th class="px-3 py-2 text-left">単価</th>
                                        <th class="px-3 py-2 text-left">金額</th>
                                        <th class="px-3 py-2 text-left">手数料</th>
                                        <th class="px-3 py-2 text-left">備考</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-t border-zinc-200 dark:border-zinc-600">
                                        <td class="px-3 py-2">2024/01/01</td>
                                        <td class="px-3 py-2">商品A</td>
                                        <td class="px-3 py-2">1</td>
                                        <td class="px-3 py-2">1000</td>
                                        <td class="px-3 py-2">1000</td>
                                        <td class="px-3 py-2">100</td>
                                        <td class="px-3 py-2">（任意）</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- インポート結果表示 -->
        @if ($importedData !== null)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            インポート結果
                        </h2>

                        @if ($importedData->count() > 0)
                            <!-- サマリー情報 -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        件数
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        {{ number_format($summary['total_count']) }}件
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        売上合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($summary['total_amount']) }}
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        手数料合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($summary['total_commission']) }}
                                    </div>
                                </div>
                            </div>

                            <!-- 一覧テーブル -->
                            <div class="overflow-x-auto">
                                <flux:table>
                                    <flux:columns>
                                        <flux:column>販売日</flux:column>
                                        <flux:column>商品名</flux:column>
                                        <flux:column>数量</flux:column>
                                        <flux:column>単価</flux:column>
                                        <flux:column>金額</flux:column>
                                        <flux:column>手数料</flux:column>
                                        <flux:column>備考</flux:column>
                                    </flux:columns>

                                    <flux:rows>
                                        @foreach ($importedData as $result)
                                            <flux:row>
                                                <flux:cell>
                                                    {{ $result->sale_date->format('Y/m/d') }}
                                                </flux:cell>
                                                <flux:cell>
                                                    {{ $result->product_name }}
                                                </flux:cell>
                                                <flux:cell>
                                                    {{ number_format($result->quantity) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    ¥{{ number_format($result->unit_price) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    ¥{{ number_format($result->amount) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    ¥{{ number_format($result->commission) }}
                                                </flux:cell>
                                                <flux:cell>
                                                    {{ $result->notes ?? '-' }}
                                                </flux:cell>
                                            </flux:row>
                                        @endforeach
                                    </flux:rows>
                                </flux:table>
                            </div>

                            <!-- 次の画面へ進むボタン -->
                            <div class="mt-6 flex justify-end">
                                <flux:button variant="primary" size="lg" wire:click="next">
                                    次の画面へ進む
                                    <flux:icon.arrow-right variant="micro" class="ml-2" />
                                </flux:button>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="text-zinc-500 mb-4">
                                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-medium">データがインポートされていません</p>
                                    <p class="mt-1 text-sm text-zinc-400">Excelファイルをアップロードしてください</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>
        @else
            <!-- データ未インポート時の次へ進むボタン -->
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6 text-center">
                        <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                            Excelファイルをアップロードすると、データが表示されます
                        </p>
                        <div class="flex justify-end">
                            <flux:button variant="primary" size="lg" wire:click="next">
                                次の画面へ進む
                                <flux:icon.arrow-right variant="micro" class="ml-2" />
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</div>
