<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

// 状態管理
state(['batchId' => null]);
state(['selectedVendorName' => '']);

// ルートパラメータからbatch_idを取得
mount(function (?string $batch = null) {
    $this->batchId = $batch;

    // 委託先名が指定されている場合は初期値として設定
    if ($batch) {
        $sessionKey = "consignment_sales_batch_{$batch}";
        $batchData = Session::get($sessionKey);
        if ($batchData && isset($batchData['vendor_name']) && $batchData['vendor_name']) {
            $this->selectedVendorName = $batchData['vendor_name'];
        }
    }
});

// 委託先名のリストを取得
$vendorNames = computed(function () {
    $vendorNames = [];

    // セッションからすべてのバッチデータを取得して委託先名を抽出
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

    // 空の値を除外してソート
    $vendorNames = array_filter($vendorNames, fn($name) => !empty($name));
    sort($vendorNames);

    return array_values($vendorNames);
});

// セッションから委託販売データを取得（データベースには保存しない）
$salesData = computed(function () {
    $allSales = collect();

    // セッションからすべてのバッチデータを取得
    $allSessions = Session::all();
    foreach ($allSessions as $key => $value) {
        if (str_starts_with($key, 'consignment_sales_batch_') && is_array($value) && isset($value['sales'])) {
            // 配列をオブジェクトのコレクションに変換して追加
            $batchSales = collect($value['sales'])->map(function ($sale) {
                // 既にオブジェクトの場合はそのまま返す（データ損失を防ぐ）
                if (is_object($sale)) {
                    return $sale;
                }

                // 配列の場合のみオブジェクト化
                return (object) $sale;
            });
            $allSales = $allSales->merge($batchSales);
        }
    }

    // 重複を排除（batch_id + product_code + client_id + sale_date + receipt_number で一意性を保証）
    $allSales = $allSales->unique(function ($item) {
        return ($item->batch_id ?? '') . '_' . ($item->product_code ?? '') . '_' . ($item->client_id ?? '') . '_' . ($item->sale_date ?? '') . '_' . ($item->receipt_number ?? '');
    });

    // 委託先名が選択されている場合、company_nameでフィルタリングとソート
    if ($this->selectedVendorName && $allSales->isNotEmpty()) {
        // 選択された委託先名と一致するcompany_nameを持つレコードを取得
        $filtered = $allSales->filter(function ($item) {
            return $item->company_name === $this->selectedVendorName;
        });

        // 一致するレコードが見つかった場合、ソートを適用
        if ($filtered->isNotEmpty()) {
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
        $filtered = $allSales->filter(function ($item) {
            return $item->vendor_name === $this->selectedVendorName;
        });

        if ($filtered->isNotEmpty()) {
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

    // 委託先名が選択されていない場合、委託販売先、クライアントID、商品コードの順でソート
    return $allSales
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

// ヘッダー情報を取得（セッションから）
$headerInfo = computed(function () {
    // 選択された委託先名に基づいてヘッダー情報を取得
    if ($this->selectedVendorName) {
        $allSessions = Session::all();
        foreach ($allSessions as $key => $value) {
            if (str_starts_with($key, 'consignment_sales_batch_') && is_array($value)) {
                $batchVendorName = $value['vendor_name'] ?? null;
                if ($batchVendorName === $this->selectedVendorName) {
                    return [
                        'vendor_name' => $value['vendor_name'] ?? null,
                        'commission_rate' => $value['commission_rate'] ?? null,
                        'billing_period' => $value['billing_period'] ?? null,
                    ];
                }
            }
        }
    }

    // batchIdが指定されている場合はそのバッチの情報を取得
    if ($this->batchId) {
        $sessionKey = "consignment_sales_batch_{$this->batchId}";
        $batchData = Session::get($sessionKey);

        if ($batchData) {
            return [
                'vendor_name' => $batchData['vendor_name'] ?? null,
                'commission_rate' => $batchData['commission_rate'] ?? null,
                'billing_period' => $batchData['billing_period'] ?? null,
            ];
        }
    }

    return [
        'vendor_name' => $this->selectedVendorName ?: null,
        'commission_rate' => null,
        'billing_period' => null,
    ];
});

// データベースから委託販売データを取得して集計
$products = computed(function () {
    $sales = $this->salesData;

    // 商品名ごとに集計
    $grouped = $sales
        ->groupBy('product_name')
        ->map(function (Collection $items) {
            return [
                'product_name' => $items->first()->product_name,
                'total_quantity' => $items->sum('quantity'),
                'total_amount' => $items->sum('amount'),
                'total_commission' => $items->sum('commission'),
                'total_net_amount' => $items->sum('net_amount'),
            ];
        })
        ->values();

    return $grouped;
});

// 売上合計を計算
$totalAmount = computed(function () {
    return $this->salesData->sum('amount');
});

// 手数料合計を計算
$totalCommission = computed(function () {
    return $this->salesData->sum('commission');
});

// 差引支払額（振込額）を計算
$totalNetAmount = computed(function () {
    return $this->salesData->sum('net_amount');
});

// 戻るボタンの処理
$back = function () {
    return $this->redirect(route('consignment-sales.index'), navigate: true);
};

// 全バッチデータを削除する関数
$clearAllBatches = function () {
    $allSessions = Session::all();
    $deletedCount = 0;

    foreach ($allSessions as $key => $value) {
        if (str_starts_with($key, 'consignment_sales_batch_')) {
            Session::forget($key);
            $deletedCount++;
        }
    }

    // 成功メッセージを設定
    session()->flash('message', "{$deletedCount}件のバッチデータを削除しました");

    // インデックスページにリダイレクト
    return $this->redirect(route('consignment-sales.index'), navigate: true);
};

// CSV配列をCSV文字列に変換するヘルパー関数
$arrayToCsv = function (array $data): string {
    $output = fopen('php://temp', 'r+');
    fputcsv($output, $data);
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return rtrim($csv, "\n");
};

// Excel出力URLを取得
$exportUrl = computed(function () {
    $params = [];

    // batchIdが指定されている場合はそれを使用
    if ($this->batchId) {
        $params['batch_id'] = $this->batchId;
    }

    // 選択された委託先名がある場合はパラメータに追加
    if ($this->selectedVendorName) {
        $params['vendor_name'] = $this->selectedVendorName;
    }

    return route('consignment-sales.settlement.export', $params);
});

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                精算書
            </h1>
            @if ($this->headerInfo['vendor_name'] || $this->headerInfo['billing_period'])
                <div class="mt-4 text-lg text-zinc-700 dark:text-zinc-300">
                    @if ($this->headerInfo['vendor_name'])
                        <span class="font-semibold">{{ $this->headerInfo['vendor_name'] }}様</span>
                    @endif
                    @if ($this->headerInfo['billing_period'])
                        <span class="mx-2">{{ $this->headerInfo['billing_period'] }}</span>
                    @endif
                    @if ($this->headerInfo['commission_rate'])
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                            （手数料率: {{ number_format($this->headerInfo['commission_rate'], 2) }}%）
                        </span>
                    @endif
                </div>
            @endif
            <p class="text-lg text-zinc-600 dark:text-zinc-400 mt-2">
                委託販売の精算結果を表示します
            </p>
        </div>

        <!-- 委託先名フィルタ -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <flux:field>
                        <flux:label>委託先名で絞り込み</flux:label>
                        <flux:select wire:model.live="selectedVendorName" placeholder="すべての委託先を表示">
                            <option value="">すべての委託先を表示</option>
                            @foreach ($this->vendorNames as $vendorName)
                                <option value="{{ $vendorName }}">{{ $vendorName }}</option>
                            @endforeach
                        </flux:select>
                        <flux:description>
                            委託先名を選択すると、該当する委託先のデータのみが表示されます
                        </flux:description>
                    </flux:field>
                </div>
            </flux:card>
        </div>

        <!-- 集計表示 -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                売上合計
                            </div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                ¥{{ number_format($this->totalAmount) }}
                            </div>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                手数料合計
                            </div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                ¥{{ number_format($this->totalCommission) }}
                            </div>
                        </div>
                        <div
                            class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border-2 border-primary-200 dark:border-primary-800">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                差引支払額（振込額）
                            </div>
                            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                ¥{{ number_format($this->totalNetAmount) }}
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- 商品明細 -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">
                        商品明細
                    </h2>

                    @if ($this->salesData->count() > 0)
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:columns>
                                    <flux:column>商品名</flux:column>
                                    <flux:column class="text-right">数量</flux:column>
                                    <flux:column class="text-right">単価</flux:column>
                                    <flux:column class="text-right">金額</flux:column>
                                    <flux:column class="text-right">手数料</flux:column>
                                    <flux:column class="text-right">差引支払額</flux:column>
                                </flux:columns>

                                <flux:rows>
                                    @foreach ($this->salesData as $sale)
                                        <flux:row>
                                            <flux:cell>
                                                {{ $sale->product_name }}
                                            </flux:cell>
                                            <flux:cell class="text-right">
                                                {{ number_format($sale->quantity) }}
                                            </flux:cell>
                                            <flux:cell class="text-right">
                                                ¥{{ number_format($sale->unit_price) }}
                                            </flux:cell>
                                            <flux:cell class="text-right">
                                                ¥{{ number_format($sale->amount) }}
                                            </flux:cell>
                                            <flux:cell class="text-right">
                                                ¥{{ number_format($sale->commission) }}
                                            </flux:cell>
                                            <flux:cell class="text-right">
                                                ¥{{ number_format($sale->net_amount) }}
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
                                <p class="mt-2 text-sm font-medium">商品データがありません</p>
                                <p class="mt-1 text-sm text-zinc-400">委託販売データをアップロードしてください</p>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- アクションボタン -->
        <div class="max-w-3xl mx-auto">
            <div class="flex justify-between gap-4">
                <flux:button variant="primary" x-data="{ isLoading: false, exportUrl: '{{ $this->exportUrl }}' }"
                    @click="isLoading = true; window.open(exportUrl, '_blank'); setTimeout(() => isLoading = false, 2000)">
                    <span x-show="!isLoading">
                        <flux:icon.arrow-down-tray variant="micro" class="mr-2" />
                        Excel出力
                    </span>
                    <span x-show="isLoading" x-cloak>
                        出力中...
                    </span>
                </flux:button>
                <div class="flex gap-2">
                    <flux:button variant="danger" wire:click="clearAllBatches"
                        onclick="return confirm('すべてのバッチデータを削除しますか？この操作は取り消せません。')">
                        <flux:icon.trash variant="micro" class="mr-2" />
                        全データ削除
                    </flux:button>
                    <flux:button variant="ghost" wire:click="back">
                        <flux:icon.arrow-left variant="micro" class="mr-2" />
                        委託販売請求書発行画面に戻る
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

</div>
