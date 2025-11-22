<?php

use App\Actions\BankTransfer\ConvertToZenkinFormat;
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
    'excelData' => null,
    'convertedFile' => null,
    'isUploading' => false,
    'isConverting' => false,
    'uploadError' => null,
    'conversionError' => null,
    'previewData' => null,
]);

// バリデーションルール
rules([
    'excelFile' => 'nullable|file|mimes:xlsx,xls|max:10240', // 10MBまで
]);

// Excelファイルの読み込み処理
$importExcel = function ($file) {
    $this->isUploading = true;
    $this->uploadError = null;
    $this->excelData = null;
    $this->previewData = null;

    try {
        // ファイルを一時保存
        $path = $file->store('temp', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // PhpSpreadsheetでExcelファイルを読み込む
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // ヘッダー行を取得（1行目）
        if (empty($rows)) {
            throw new \Exception('Excelファイルが空です');
        }

        $headerRow = array_shift($rows);
        $headerRow = array_map('trim', $headerRow);

        // データをパース
        $parsedData = [];
        foreach ($rows as $rowIndex => $row) {
            // 空行をスキップ
            if (empty(array_filter($row))) {
                continue;
            }

            // ヘッダーに基づいて連想配列に変換
            $rowData = [];
            foreach ($headerRow as $colIndex => $header) {
                $rowData[$header] = $row[$colIndex] ?? null;
            }

            // 必須項目のチェック
            if (empty($rowData['金融機関コード']) || empty($rowData['口座番号']) || empty($rowData['振込金額'])) {
                continue;
            }

            $parsedData[] = $rowData;
        }

        if (empty($parsedData)) {
            throw new \Exception('有効なデータが見つかりませんでした');
        }

        // 一時ファイルを削除
        Storage::disk('local')->delete($path);

        // データを保存
        $this->excelData = collect($parsedData);
        $this->previewData = $this->excelData->take(10); // プレビュー用に10件まで

        $this->isUploading = false;
    } catch (\Exception $e) {
        Log::error('Excel読み込みエラー: ' . $e->getMessage());
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

// 全銀フォーマットへの変換処理（テキスト形式）
$convertToZenkinFormat = function () {
    $this->isConverting = true;
    $this->conversionError = null;
    $this->convertedFile = null;

    try {
        if ($this->excelData === null || $this->excelData->isEmpty()) {
            throw new \Exception('変換するデータがありません');
        }

        // 変換実行（各行から自動的に情報を取得するため、configは空配列）
        $converter = new ConvertToZenkinFormat();
        $zenkinContent = $converter->convert($this->excelData, []);

        // 一時ファイルとして保存
        $filename = 'zenkin_format_' . now()->format('YmdHis') . '_' . uniqid() . '.txt';
        $path = 'temp/' . $filename;
        Storage::disk('local')->put($path, $zenkinContent);

        $this->convertedFile = $filename;
        $this->isConverting = false;
    } catch (\Exception $e) {
        Log::error('全銀フォーマット変換エラー: ' . $e->getMessage());
        $this->conversionError = '変換に失敗しました: ' . $e->getMessage();
        $this->isConverting = false;
    }
};

// ファイルダウンロードURLを取得
$getDownloadUrl = function () {
    if ($this->convertedFile === null) {
        return null;
    }

    return route('bank-transfers.download', ['file' => $this->convertedFile]);
};

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                全銀フォーマット変換
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                Excelファイルをアップロードして全銀フォーマットに変換します
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
                            1行目はヘッダー行として扱われます。以下の列を含めてください：
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-zinc-100 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">顧客ID</th>
                                        <th class="px-3 py-2 text-left">事業者名</th>
                                        <th class="px-3 py-2 text-left">金融機関コード</th>
                                        <th class="px-3 py-2 text-left">金融機関名</th>
                                        <th class="px-3 py-2 text-left">支店コード</th>
                                        <th class="px-3 py-2 text-left">支店名</th>
                                        <th class="px-3 py-2 text-left">預金種目</th>
                                        <th class="px-3 py-2 text-left">口座番号</th>
                                        <th class="px-3 py-2 text-left">口座名義（カナ）</th>
                                        <th class="px-3 py-2 text-left">振込金額</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- データプレビュー -->
        @if ($previewData !== null && $previewData->count() > 0)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            データプレビュー（最大10件）
                        </h2>
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:columns>
                                    <flux:column>顧客ID</flux:column>
                                    <flux:column>事業者名</flux:column>
                                    <flux:column>金融機関名</flux:column>
                                    <flux:column>支店名</flux:column>
                                    <flux:column>口座番号</flux:column>
                                    <flux:column>振込金額</flux:column>
                                </flux:columns>
                                <flux:rows>
                                    @foreach ($previewData as $row)
                                        <flux:row>
                                            <flux:cell>{{ $row['顧客ID'] ?? '-' }}</flux:cell>
                                            <flux:cell>{{ $row['事業者名'] ?? '-' }}</flux:cell>
                                            <flux:cell>{{ $row['金融機関名'] ?? '-' }}</flux:cell>
                                            <flux:cell>{{ $row['支店名'] ?? '-' }}</flux:cell>
                                            <flux:cell>{{ $row['口座番号'] ?? '-' }}</flux:cell>
                                            <flux:cell>¥{{ number_format((int) ($row['振込金額'] ?? 0)) }}</flux:cell>
                                        </flux:row>
                                    @endforeach
                                </flux:rows>
                            </flux:table>
                        </div>
                        @if ($excelData && $excelData->count() > 10)
                            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                                全{{ $excelData->count() }}件中10件を表示しています
                            </p>
                        @endif
                    </div>
                </flux:card>
            </div>
        @endif

        <!-- 変換ボタン -->
        @if ($excelData !== null && $excelData->count() > 0)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            全銀フォーマット変換
                        </h2>

                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                            Excelファイルの各行から全銀フォーマットのヘッダーレコードとデータレコードを自動生成します。
                            各行ごとに独立したレコードセットが作成されます。
                        </p>

                        <!-- 変換エラーメッセージ -->
                        @if ($conversionError)
                            <div class="mb-4">
                                <flux:callout variant="danger">
                                    {{ $conversionError }}
                                </flux:callout>
                            </div>
                        @endif

                        <div class="flex gap-4 justify-end">
                            <flux:button wire:click="convertToZenkinFormat" variant="primary" size="lg"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="convertToZenkinFormat">
                                    テキスト形式で変換
                                </span>
                                <span wire:loading wire:target="convertToZenkinFormat">
                                    変換中...
                                </span>
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif

        <!-- ダウンロードボタン -->
        @if ($convertedFile !== null)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            変換完了
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                            全銀フォーマットへの変換が完了しました。ファイルをダウンロードしてください。
                        </p>
                        <div class="flex justify-end">
                            <a href="{{ $this->getDownloadUrl() }}" download>
                                <flux:button variant="primary" size="lg">
                                    ファイルをダウンロード
                                    <flux:icon.arrow-down variant="micro" class="ml-2" />
                                </flux:button>
                            </a>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</div>
