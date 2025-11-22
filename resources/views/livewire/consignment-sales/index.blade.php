<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

use function Livewire\Volt\computed;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\updated;
use function Livewire\Volt\uses;

// ファイルアップロード機能を有効化
uses(WithFileUploads::class);

// 状態管理
state([
    'vendor_name' => '',
    'commission_rate' => 10,
    'billing_period' => '',
    'excelFile' => null,
    'importedData' => null,
    'summary' => null,
    'productSummary' => null,
    'isUploading' => false,
    'uploadError' => null,
    'currentBatchId' => null,
    'isCustomVendor' => false, // カスタム委託先名入力フラグ
]);

// 既存の委託販売先リストを取得（セッションから + インポートしたExcelデータから）
$vendorNames = computed(function () {
    $vendorNames = [];

    // 1. セッションからすべてのバッチデータを取得して委託先名を抽出
    $allSessions = Session::all();
    foreach ($allSessions as $key => $value) {
        if (str_starts_with($key, 'consignment_sales_batch_') && is_array($value)) {
            // vendor_nameが設定されている場合は追加
            if (isset($value['vendor_name']) && $value['vendor_name']) {
                $vendorName = $value['vendor_name'];
                if (!in_array($vendorName, $vendorNames)) {
                    $vendorNames[] = $vendorName;
                }
            }

            // インポートしたデータから会社名を抽出
            if (isset($value['sales']) && is_array($value['sales'])) {
                foreach ($value['sales'] as $sale) {
                    $companyName = is_array($sale) ? $sale['company_name'] ?? null : $sale->company_name ?? null;
                    if ($companyName && !in_array($companyName, $vendorNames)) {
                        $vendorNames[] = $companyName;
                    }
                }
            }
        }
    }

    // 2. 現在インポートしたデータから会社名を抽出
    if ($this->importedData && $this->importedData->isNotEmpty()) {
        foreach ($this->importedData as $sale) {
            $companyName = is_object($sale) ? $sale->company_name ?? null : $sale['company_name'] ?? null;
            if ($companyName && !in_array($companyName, $vendorNames)) {
                $vendorNames[] = $companyName;
            }
        }
    }

    // 空の値を除外してソート
    $vendorNames = array_filter($vendorNames, fn($name) => !empty($name));
    sort($vendorNames);

    return array_values($vendorNames);
});

// フィルタリングされた委託販売データを取得（セッションから）
$filteredData = computed(function () {
    // vendor_nameが選択されている場合、その委託先のデータのみを取得
    if ($this->vendor_name && $this->vendor_name !== '__custom__') {
        // 現在のバッチのデータで、選択した委託先のデータのみを取得
        if ($this->currentBatchId && $this->importedData && $this->importedData->isNotEmpty()) {
            // 選択された委託先名（vendor_name）と一致するcompany_nameを持つレコードを取得
            $matchingRecords = $this->importedData->filter(function ($item) {
                return $item->company_name === $this->vendor_name;
            });

            // 一致するレコードが見つかった場合、そのクライアントIDを取得
            if ($matchingRecords->isNotEmpty()) {
                // 最初のレコードのクライアントIDを取得（同じ委託先なら同じクライアントIDのはず）
                $clientIds = $matchingRecords->pluck('client_id')->filter()->unique()->values();

                // 同じクライアントIDを持つレコードでフィルタリング
                $filtered = $this->importedData->filter(function ($item) use ($clientIds) {
                    return $clientIds->contains($item->client_id);
                });

                // 委託販売先、クライアントID、商品コードの順でソート
                return $filtered
                    ->sortBy([
                        [
                            function ($item) {
                                return $item->company_name ?? ($item->vendor_name ?? 'zzz');
                            },
                            'asc',
                        ],
                        [
                            function ($item) {
                                return $item->client_id ?? 'zzz';
                            },
                            'asc',
                        ],
                        [
                            function ($item) {
                                return $item->product_code ?? 'zzz';
                            },
                            'asc',
                        ],
                    ])
                    ->values();
            }

            // company_nameで一致しない場合は、vendor_nameでフィルタリング（フォールバック）
            $filtered = $this->importedData->filter(function ($item) {
                return $item->vendor_name === $this->vendor_name;
            });

            return $filtered
                ->sortBy([
                    [
                        function ($item) {
                            return $item->company_name ?? ($item->vendor_name ?? 'zzz');
                        },
                        'asc',
                    ],
                    [
                        function ($item) {
                            return $item->client_id ?? 'zzz';
                        },
                        'asc',
                    ],
                    [
                        function ($item) {
                            return $item->product_code ?? 'zzz';
                        },
                        'asc',
                    ],
                ])
                ->values();
        }
    }

    // vendor_nameが選択されていない場合、現在のバッチのデータを表示
    // 委託販売先、クライアントID、商品コードの順でソート
    $data = $this->importedData ?? collect();

    return $data
        ->sortBy([
            [
                function ($item) {
                    return $item->company_name ?? ($item->vendor_name ?? 'zzz');
                },
                'asc',
            ],
            [
                function ($item) {
                    return $item->client_id ?? 'zzz';
                },
                'asc',
            ],
            [
                function ($item) {
                    return $item->product_code ?? 'zzz';
                },
                'asc',
            ],
        ])
        ->values();
});

// バリデーションルール
// vendor_nameはファイルアップロード後に選択するため、ここではnullableに設定
rules([
    'vendor_name' => 'nullable|string|max:255',
    'commission_rate' => 'required|numeric|min:0|max:100',
    'billing_period' => 'nullable|string|max:255',
    'excelFile' => 'nullable|file|mimes:xlsx,xls|max:10240', // 10MBまで
]);

// Excelファイルのインポート処理
$importExcel = function ($file) {
    // バリデーション（委託先名はファイルアップロード後に入力するため、ここでは必須にしない）
    $this->validate([
        'commission_rate' => 'required|numeric|min:0|max:100',
    ]);

    $this->isUploading = true;
    $this->uploadError = null;

    try {
        // 一意なbatch_idを生成
        $batchId = Str::uuid()->toString();
        $this->currentBatchId = $batchId;

        // ファイルを一時保存
        $path = $file->store('temp', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // PhpSpreadsheetでExcelファイルを読み込む
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // ヘッダー行をスキップ（1行目を想定）
        $headerRow = array_shift($rows);

        // デバッグ: ヘッダー行をログに出力
        Log::info('Excelヘッダー行:', ['headers' => $headerRow]);

        // ヘッダー行から列の位置を動的に取得
        $columnIndexes = [];
        $headerKeywords = [
            'sale_date' => ['売上日', '販売日', '日付', 'date'],
            'receipt_number' => ['レシート番号', 'レシート', 'receipt'],
            'client_id' => ['クライアントID', 'クライアント', 'client'],
            'company_name' => ['会社名', '委託先', 'company'],
            'product_code' => ['商品コード', 'コード', 'product_code'],
            'product_name' => ['商品名', '商品', 'product'],
            'unit_price' => ['単価', '価格', 'unit_price', 'price'],
            'quantity' => ['販売数', '数量', 'quantity', '数'],
            'amount' => ['売上金額', '金額', 'amount', '合計'],
            'category' => ['カテゴリ', 'カテゴリー', 'category'],
        ];

        foreach ($headerKeywords as $key => $keywords) {
            foreach ($headerRow as $index => $header) {
                $headerLower = mb_strtolower(trim($header ?? ''));
                foreach ($keywords as $keyword) {
                    if (str_contains($headerLower, mb_strtolower($keyword))) {
                        $columnIndexes[$key] = $index;
                        break 2;
                    }
                }
            }
        }

        // デバッグ: 列の位置をログに出力
        Log::info('列の位置:', ['column_indexes' => $columnIndexes]);

        // 列の位置が取得できなかった場合は、デフォルトの位置を使用
        $getColumnValue = function ($row, $key, $defaultIndex, $defaultValue = null) use ($columnIndexes) {
            $index = $columnIndexes[$key] ?? $defaultIndex;

            return $row[$index] ?? $defaultValue;
        };

        // データをパースして保存
        $importedCount = 0;
        $importedRecords = [];

        foreach ($rows as $index => $row) {
            // 空行をスキップ
            if (empty(array_filter($row))) {
                continue;
            }

            // デバッグ: 最初の3行のデータをログに出力
            if ($index < 3) {
                Log::info("Excelデータ行 {$index}:", [
                    'row' => $row,
                    'row_count' => count($row),
                ]);
            }

            // ヘッダー行から取得した列の位置を使用してデータを取得
            $saleDate = $getColumnValue($row, 'sale_date', 0);
            $receiptNumber = $getColumnValue($row, 'receipt_number', 1);
            $clientId = $getColumnValue($row, 'client_id', 2);
            $companyName = $getColumnValue($row, 'company_name', 3);
            $productCode = $getColumnValue($row, 'product_code', 4);
            $productName = $getColumnValue($row, 'product_name', 5);
            $unitPrice = $getColumnValue($row, 'unit_price', 6, 0);
            $quantity = $getColumnValue($row, 'quantity', 7, 1);
            $amount = $getColumnValue($row, 'amount', 8, 0);
            $category = $getColumnValue($row, 'category', 9);

            // デバッグ: 最初の3行のパース結果をログに出力
            if ($index < 3) {
                $unitPriceIndex = $columnIndexes['unit_price'] ?? 6;
                $quantityIndex = $columnIndexes['quantity'] ?? 7;
                Log::info("パース結果 {$index}:", [
                    'unit_price_index' => $unitPriceIndex,
                    'unit_price_raw' => $row[$unitPriceIndex] ?? 'not_set',
                    'unit_price_parsed' => $unitPrice,
                    'quantity_index' => $quantityIndex,
                    'quantity_raw' => $row[$quantityIndex] ?? 'not_set',
                    'quantity_parsed' => $quantity,
                    'product_name' => $productName,
                ]);
            }

            // 必須項目のチェック（商品名は必須）
            if (empty($productName)) {
                continue;
            }

            // 数値の検証と変換（カンマ区切りの数値に対応）
            // カンマを削除してから数値チェック
            $unitPrice = $unitPrice ? str_replace([',', '，', ' '], '', (string) $unitPrice) : '0';
            $quantity = $quantity ? str_replace([',', '，', ' '], '', (string) $quantity) : '1';
            $amount = $amount ? str_replace([',', '，', ' '], '', (string) $amount) : '0';

            $unitPrice = is_numeric($unitPrice) ? (int) $unitPrice : 0;
            $quantity = is_numeric($quantity) ? (int) $quantity : 1;
            $amount = is_numeric($amount) ? (int) $amount : 0;

            // 金額が0の場合は単価×数量で計算
            if ($amount === 0 && $unitPrice > 0 && $quantity > 0) {
                $amount = $unitPrice * $quantity;
            }

            // 手数料を計算（端数切り捨て）
            $commission = (int) floor($amount * ($this->commission_rate / 100));

            // 実際に支払う金額を計算
            $netAmount = $amount - $commission;

            // 販売日の処理（日付形式の検証）
            if (empty($saleDate)) {
                $saleDate = now()->format('Y-m-d');
            } else {
                // Excelの日付形式を変換（必要に応じて）
                try {
                    if (is_numeric($saleDate)) {
                        // Excelのシリアル値の場合
                        $saleDateObj = SpreadsheetDate::excelToDateTimeObject($saleDate);
                        $saleDate = $saleDateObj->format('Y-m-d');
                    } else {
                        // 文字列の場合、日付形式を検証
                        $saleDateObj = new \DateTime($saleDate);
                        $saleDate = $saleDateObj->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // 日付として解釈できない場合は現在の日付を使用
                    $saleDate = now()->format('Y-m-d');
                }
            }

            // 備考に請求期間を追加
            $notes = [];
            if ($this->billing_period) {
                $notes[] = "請求期間: {$this->billing_period}";
            }
            $notesText = !empty($notes) ? implode(' / ', $notes) : null;

            // データを配列として保持（データベースには保存しない）
            $saleData = [
                'batch_id' => $batchId,
                'vendor_name' => $this->vendor_name,
                'commission_rate' => $this->commission_rate,
                'sale_date' => $saleDate,
                'receipt_number' => $receiptNumber,
                'client_id' => $clientId,
                'company_name' => $companyName,
                'product_code' => $productCode,
                'product_name' => $productName,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'category' => $category,
                'commission' => $commission,
                'net_amount' => $netAmount,
                'notes' => $notesText,
            ];

            // セッション保存用は配列のまま（データ損失を防ぐ）
            $importedRecords[] = $saleData;
            $importedCount++;
        }

        // 一時ファイルを削除
        Storage::disk('local')->delete($path);

        // インポートしたデータを表示用に設定（オブジェクト化）
        $this->importedData = collect($importedRecords)->map(function ($sale) {
            return (object) $sale;
        });

        // セッションにデータを保存（データベースには保存しない）
        // 配列のまま保存することで、シリアライズ/デシリアライズ時のデータ損失を防ぐ
        Session::put("consignment_sales_batch_{$batchId}", [
            'batch_id' => $batchId,
            'vendor_name' => $this->vendor_name,
            'commission_rate' => $this->commission_rate,
            'billing_period' => $this->billing_period,
            'sales' => $importedRecords, // 配列のまま保存
        ]);

        // 集計サマリーを計算
        $this->summary = [
            'total_count' => $importedCount,
            'total_amount' => $this->importedData->sum('amount'),
            'total_commission' => $this->importedData->sum('commission'),
            'total_net_amount' => $this->importedData->sum('net_amount'),
        ];

        // 商品ごとの集計を計算
        $this->productSummary = $this->importedData
            ->groupBy('product_name')
            ->map(function ($items) {
                return [
                    'product_name' => $items->first()->product_name,
                    'total_quantity' => $items->sum('quantity'),
                    'total_amount' => $items->sum('amount'),
                    'total_commission' => $items->sum('commission'),
                    'total_net_amount' => $items->sum('net_amount'),
                    'count' => $items->count(),
                ];
            })
            ->values()
            ->sortBy('product_name');

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
    // 委託先名が変更されたときに、既にインポート済みのデータも更新
    'vendor_name' => function ($value) {
        // 「新規入力」が選択された場合は、カスタム入力モードに切り替え
        if ($value === '__custom__') {
            $this->isCustomVendor = true;
            $this->vendor_name = '';

            return;
        }

        if ($this->currentBatchId && $value) {
            // セッション内のデータを更新
            $sessionKey = "consignment_sales_batch_{$this->currentBatchId}";
            $batchData = Session::get($sessionKey);
            if ($batchData) {
                $batchData['vendor_name'] = $value;
                // 各売上レコードのvendor_nameも更新
                foreach ($batchData['sales'] as &$sale) {
                    if (is_object($sale)) {
                        $sale->vendor_name = $value;
                    } else {
                        $sale['vendor_name'] = $value;
                    }
                }
                Session::put($sessionKey, $batchData);
            }

            // 表示用データも更新
            if ($this->importedData) {
                $this->importedData->each(function ($item) use ($value) {
                    $item->vendor_name = $value;
                });
            }
        }
    },
]);

// 次の画面へ進む
$next = function () {
    // バッチIDが設定されていない場合はエラー
    if (!$this->currentBatchId) {
        return;
    }

    // 委託先名のバリデーション
    $this->validate([
        'vendor_name' => 'required|string|max:255',
    ]);

    // セッションに最新のデータを保存（vendor_nameの更新を反映）
    $sessionKey = "consignment_sales_batch_{$this->currentBatchId}";
    $batchData = Session::get($sessionKey);
    if ($batchData) {
        $batchData['vendor_name'] = $this->vendor_name;
        $batchData['commission_rate'] = $this->commission_rate;
        $batchData['billing_period'] = $this->billing_period;
        // 各売上レコードも更新
        foreach ($batchData['sales'] as &$sale) {
            if (is_object($sale)) {
                $sale->vendor_name = $this->vendor_name;
                $sale->commission_rate = $this->commission_rate;
            } else {
                $sale['vendor_name'] = $this->vendor_name;
                $sale['commission_rate'] = $this->commission_rate;
            }
        }
        Session::put($sessionKey, $batchData);
    }

    // 精算書発行画面への遷移（batch_idをパラメータとして渡す）
    return $this->redirect(route('consignment-sales.settlement', ['batch' => $this->currentBatchId]), navigate: true);
};

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                委託販売精算書発行
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
                                        <th class="px-3 py-2 text-left">売上日</th>
                                        <th class="px-3 py-2 text-left">レシート番号</th>
                                        <th class="px-3 py-2 text-left">クライアントID</th>
                                        <th class="px-3 py-2 text-left">会社名</th>
                                        <th class="px-3 py-2 text-left">商品コード</th>
                                        <th class="px-3 py-2 text-left">商品名</th>
                                        <th class="px-3 py-2 text-left">単価</th>
                                        <th class="px-3 py-2 text-left">販売数</th>
                                        <th class="px-3 py-2 text-left">売上金額</th>
                                        <th class="px-3 py-2 text-left">カテゴリ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-t border-zinc-200 dark:border-zinc-600">
                                        <td class="px-3 py-2">2025/11/22</td>
                                        <td class="px-3 py-2">R001</td>
                                        <td class="px-3 py-2">C001</td>
                                        <td class="px-3 py-2">株式会社サンプル</td>
                                        <td class="px-3 py-2">P000</td>
                                        <td class="px-3 py-2">商品A</td>
                                        <td class="px-3 py-2">1,000</td>
                                        <td class="px-3 py-2">10</td>
                                        <td class="px-3 py-2">10,000</td>
                                        <td class="px-3 py-2">カテゴリA</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- 入力フォーム -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                        委託販売情報入力
                    </h2>

                    <div class="space-y-4">
                        <!-- 委託先名 -->
                        <flux:field>
                            <flux:label>委託先名</flux:label>
                            @if ($importedData !== null && $importedData->count() > 0)
                                <!-- ファイルアップロード後はセレクトボックスで選択 -->
                                @if (!$isCustomVendor)
                                    <flux:select wire:model.live="vendor_name">
                                        <option value="">委託先を選択してください</option>
                                        @if (count($this->vendorNames) > 0)
                                            @foreach ($this->vendorNames as $vendorName)
                                                <option value="{{ $vendorName }}">{{ $vendorName }}</option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>Excelデータから会社名が見つかりませんでした</option>
                                        @endif
                                        <option value="__custom__">新規入力</option>
                                    </flux:select>
                                    <flux:error name="vendor_name" />
                                    @if (count($this->vendorNames) > 0)
                                        <flux:description>
                                            Excelデータから抽出した会社名（{{ count($this->vendorNames) }}件）から選択するか、「新規入力」を選択して手動で入力してください。
                                        </flux:description>
                                    @else
                                        <flux:description>
                                            Excelデータから会社名が見つかりませんでした。「新規入力」を選択して手動で入力してください。
                                        </flux:description>
                                    @endif
                                @else
                                    <div class="space-y-2">
                                        <flux:input wire:model="vendor_name" placeholder="委託先名を入力してください" />
                                        <flux:button variant="ghost" size="sm"
                                            wire:click="$set('isCustomVendor', false)">
                                            リストから選択に戻る
                                        </flux:button>
                                    </div>
                                    <flux:error name="vendor_name" />
                                    <flux:description>委託先名を入力してください</flux:description>
                                @endif
                            @else
                                <!-- ファイルアップロード前はテキスト入力 -->
                                <flux:input wire:model="vendor_name" placeholder="委託先名を入力してください" />
                                <flux:error name="vendor_name" />
                                <flux:description>Excelファイルをアップロードすると、データから会社名が自動的に抽出され、リストから選択できるようになります。
                                </flux:description>
                            @endif
                        </flux:field>

                        <!-- 手数料率 -->
                        <flux:field>
                            <flux:label>手数料率（%）</flux:label>
                            <flux:input type="number" wire:model="commission_rate" placeholder="10" min="0"
                                max="100" step="0.01" />
                            <flux:error name="commission_rate" />
                            <flux:description>手数料率をパーセントで入力してください（例: 10）</flux:description>
                        </flux:field>

                        <!-- 請求期間 -->
                        <flux:field>
                            <flux:label>請求期間</flux:label>
                            <flux:input wire:model="billing_period" placeholder="例: 2023年10月分" />
                            <flux:error name="billing_period" />
                            <flux:description>請求期間を入力してください（任意）</flux:description>
                        </flux:field>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- アップロードしたExcelデータ表示 -->
        @if ($this->filteredData->count() > 0)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">
                                @if ($vendor_name && $vendor_name !== '__custom__')
                                    委託販売データ（{{ $vendor_name }}）
                                @else
                                    アップロードしたExcelデータ
                                @endif
                            </h2>
                            @if ($vendor_name && $vendor_name !== '__custom__')
                                <flux:button variant="ghost" size="sm" wire:click="$set('vendor_name', '')">
                                    フィルター解除
                                </flux:button>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-zinc-100 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">売上日</th>
                                        <th class="px-3 py-2 text-left">レシート番号</th>
                                        <th class="px-3 py-2 text-left">クライアントID</th>
                                        <th class="px-3 py-2 text-left">会社名</th>
                                        <th class="px-3 py-2 text-left">商品コード</th>
                                        <th class="px-3 py-2 text-left">商品名</th>
                                        <th class="px-3 py-2 text-left">単価</th>
                                        <th class="px-3 py-2 text-left">販売数</th>
                                        <th class="px-3 py-2 text-left">売上金額</th>
                                        <th class="px-3 py-2 text-left">カテゴリ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->filteredData as $result)
                                        <tr class="border-t border-zinc-200 dark:border-zinc-600">
                                            <td class="px-3 py-2">
                                                @if ($result->sale_date)
                                                    @if (is_string($result->sale_date))
                                                        {{ \Carbon\Carbon::parse($result->sale_date)->format('Y/m/d') }}
                                                    @else
                                                        {{ $result->sale_date->format('Y/m/d') }}
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">{{ $result->receipt_number ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $result->client_id ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $result->company_name ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $result->product_code ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $result->product_name }}</td>
                                            <td class="px-3 py-2">{{ number_format($result->unit_price) }}</td>
                                            <td class="px-3 py-2">{{ number_format($result->quantity) }}</td>
                                            <td class="px-3 py-2">{{ number_format($result->amount) }}</td>
                                            <td class="px-3 py-2">{{ $result->category ?? '-' }}</td>
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
        @if ($this->filteredData->count() > 0 || $importedData !== null)
            <div class="mb-8 max-w-3xl mx-auto">
                <flux:card>
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                            インポート結果
                            @if ($vendor_name && $vendor_name !== '__custom__')
                                <span class="text-sm font-normal text-zinc-500 dark:text-zinc-400">
                                    （{{ $vendor_name }}でフィルタリング中）
                                </span>
                            @endif
                        </h2>

                        @if ($this->filteredData->count() > 0)
                            <!-- サマリー情報 -->
                            @php
                                $filteredSummary = [
                                    'total_count' => $this->filteredData->count(),
                                    'total_amount' => $this->filteredData->sum('amount'),
                                    'total_commission' => $this->filteredData->sum('commission'),
                                    'total_net_amount' => $this->filteredData->sum('net_amount'),
                                ];
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        件数
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        {{ number_format($filteredSummary['total_count']) }}件
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        売上合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($filteredSummary['total_amount']) }}
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        手数料合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($filteredSummary['total_commission']) }}
                                    </div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                        支払金額合計
                                    </div>
                                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                        ¥{{ number_format($filteredSummary['total_net_amount'] ?? 0) }}
                                    </div>
                                </div>
                            </div>

                            <!-- 商品ごとの集計 -->
                            @php
                                $filteredProductSummary = $this->filteredData
                                    ->groupBy('product_name')
                                    ->map(function ($items) {
                                        return [
                                            'product_name' => $items->first()->product_name,
                                            'total_quantity' => $items->sum('quantity'),
                                            'total_amount' => $items->sum('amount'),
                                            'total_commission' => $items->sum('commission'),
                                            'total_net_amount' => $items->sum('net_amount'),
                                            'count' => $items->count(),
                                        ];
                                    })
                                    ->values()
                                    ->sortBy('product_name');
                            @endphp
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                                    商品ごとの集計
                                </h3>
                                <div class="overflow-x-auto">
                                    <flux:table>
                                        <flux:columns>
                                            <flux:column>商品名</flux:column>
                                            <flux:column class="text-right">件数</flux:column>
                                            <flux:column class="text-right">数量合計</flux:column>
                                            <flux:column class="text-right">金額合計</flux:column>
                                            <flux:column class="text-right">手数料合計</flux:column>
                                            <flux:column class="text-right">支払金額合計</flux:column>
                                        </flux:columns>

                                        <flux:rows>
                                            @foreach ($filteredProductSummary as $product)
                                                <flux:row>
                                                    <flux:cell>
                                                        <span
                                                            class="font-medium">{{ $product['product_name'] }}</span>
                                                    </flux:cell>
                                                    <flux:cell class="text-right">
                                                        {{ number_format($product['count']) }}件
                                                    </flux:cell>
                                                    <flux:cell class="text-right">
                                                        {{ number_format($product['total_quantity']) }}
                                                    </flux:cell>
                                                    <flux:cell class="text-right">
                                                        ¥{{ number_format($product['total_amount']) }}
                                                    </flux:cell>
                                                    <flux:cell class="text-right">
                                                        ¥{{ number_format($product['total_commission']) }}
                                                    </flux:cell>
                                                    <flux:cell class="text-right">
                                                        <span
                                                            class="font-semibold text-primary-600 dark:text-primary-400">
                                                            ¥{{ number_format($product['total_net_amount']) }}
                                                        </span>
                                                    </flux:cell>
                                                </flux:row>
                                            @endforeach
                                        </flux:rows>
                                    </flux:table>
                                </div>
                            </div>

                            <!-- 一覧テーブル -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                                    明細一覧
                                </h3>
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
                                            @foreach ($this->filteredData as $result)
                                                <flux:row>
                                                    <flux:cell>
                                                        @if (is_string($result->sale_date))
                                                            {{ \Carbon\Carbon::parse($result->sale_date)->format('Y/m/d') }}
                                                        @else
                                                            {{ $result->sale_date->format('Y/m/d') }}
                                                        @endif
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
                                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
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
