<?php

use App\Models\ConsignmentSale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\updated;
use function Livewire\Volt\uses;

// ファイルアップロード機能を有効化
uses(WithFileUploads::class);

// 状態管理
state([
    'excelFile' => null,
    'importedData' => null,
    'summary' => null,
    'isUploading' => false,
    'uploadError' => null,
]);

// バリデーションルール
rules([
    'excelFile' => 'nullable|file|mimes:xlsx,xls|max:10240', // 10MBまで
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

            // Excelの列順序を想定: 商品コード, 商品名, 単価, 販売数, 売上金額
            $productCode = $row[0] ?? null;
            $productName = $row[1] ?? null;
            $unitPrice = $row[2] ?? 0;
            $quantity = $row[3] ?? 1;
            $amount = $row[4] ?? 0;

            // 必須項目のチェック（商品名は必須）
            if (empty($productName)) {
                continue;
            }

            // 数値の検証と変換
            $unitPrice = is_numeric($unitPrice) ? (int) $unitPrice : 0;
            $quantity = is_numeric($quantity) ? (int) $quantity : 1;
            $amount = is_numeric($amount) ? (int) $amount : 0;

            // 金額が0の場合は単価×数量で計算
            if ($amount === 0 && $unitPrice > 0 && $quantity > 0) {
                $amount = $unitPrice * $quantity;
            }

            // 販売日は現在の日付を使用（説明テーブルにないため）
            $saleDate = now()->format('Y-m-d');

            // データを保存
            $consignmentSale = ConsignmentSale::create([
                'sale_date' => $saleDate,
                'product_name' => $productName,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'commission' => 0, // 手数料はデフォルト0
                'notes' => $productCode ? "商品コード: {$productCode}" : null, // 商品コードを備考に保存
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

// 次の画面へ進む
$next = function () {
    // 精算書発行画面への遷移
    return $this->redirect(route('consignment-sales.settlement'), navigate: true);
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
                Excelファイルをアップロードして委託販売データを登録します
            </p>
        </div>

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
                                        <th class="px-3 py-2 text-left">商品コード</th>
                                        <th class="px-3 py-2 text-left">商品名</th>
                                        <th class="px-3 py-2 text-left">単価</th>
                                        <th class="px-3 py-2 text-left">販売数</th>
                                        <th class="px-3 py-2 text-left">売上金額</th>
                                        {{-- <th class="px-3 py-2 text-left">手数料</th> --}}
                                        {{-- <th class="px-3 py-2 text-left">備考</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-t border-zinc-200 dark:border-zinc-600">
                                        <td class="px-3 py-2">P000</td>
                                        <td class="px-3 py-2">商品A</td>
                                        <td class="px-3 py-2">1,000</td>
                                        <td class="px-3 py-2">10</td>
                                        <td class="px-3 py-2">10,000</td>
                                        {{-- <td class="px-3 py-2">100</td> --}}
                                        {{-- <td class="px-3 py-2">（任意）</td> --}}
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- アップロードしたExcelデータ表示 -->
        @if ($importedData !== null && $importedData->count() > 0)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            アップロードしたExcelデータ
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-zinc-100 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">商品コード</th>
                                        <th class="px-3 py-2 text-left">商品名</th>
                                        <th class="px-3 py-2 text-left">単価</th>
                                        <th class="px-3 py-2 text-left">販売数</th>
                                        <th class="px-3 py-2 text-left">売上金額</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($importedData as $result)
                                        @php
                                            // 備考から商品コードを抽出
                                            $productCode = '-';
                                            if (
                                                $result->notes &&
                                                preg_match('/商品コード:\s*(.+)/', $result->notes, $matches)
                                            ) {
                                                $productCode = trim($matches[1]);
                                            }
                                        @endphp
                                        <tr class="border-t border-zinc-200 dark:border-zinc-600">
                                            <td class="px-3 py-2">{{ $productCode }}</td>
                                            <td class="px-3 py-2">{{ $result->product_name }}</td>
                                            <td class="px-3 py-2">{{ number_format($result->unit_price) }}</td>
                                            <td class="px-3 py-2">{{ number_format($result->quantity) }}</td>
                                            <td class="px-3 py-2">{{ number_format($result->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif

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
        @endif
    </div>
</div>
