<?php

use App\Models\ConsignmentSale;
use Illuminate\Support\Collection;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

// 状態管理
state(['batchId' => null]);

// ルートパラメータからbatch_idを取得
mount(function (?string $batch = null) {
    $this->batchId = $batch;
});

// データベースから委託販売データを取得
$salesData = computed(function () {
    $query = ConsignmentSale::query();
    
    if ($this->batchId) {
        $query->where('batch_id', $this->batchId);
    }
    
    return $query->orderBy('created_at', 'desc')->get();
});

// ヘッダー情報を取得（最初のレコードから）
$headerInfo = computed(function () {
    $sales = $this->salesData;
    
    if ($sales->isEmpty()) {
        return [
            'vendor_name' => null,
            'commission_rate' => null,
            'billing_period' => null,
        ];
    }
    
    $firstSale = $sales->first();
    $billingPeriod = null;
    
    // 備考から請求期間を抽出
    if ($firstSale->notes && preg_match('/請求期間:\s*(.+?)(?:\s*\/|$)/', $firstSale->notes, $matches)) {
        $billingPeriod = trim($matches[1]);
    }
    
    return [
        'vendor_name' => $firstSale->vendor_name,
        'commission_rate' => $firstSale->commission_rate,
        'billing_period' => $billingPeriod,
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

// CSV配列をCSV文字列に変換するヘルパー関数
$arrayToCsv = function (array $data): string {
    $output = fopen('php://temp', 'r+');
    fputcsv($output, $data);
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return rtrim($csv, "\n");
};

// CSV出力処理
$export = function () use ($arrayToCsv) {
    $sales = $this->salesData;
    $headerInfo = $this->headerInfo;
    
    if ($sales->isEmpty()) {
        return;
    }
    
    $vendorName = $headerInfo['vendor_name'] ?? '委託先';
    // ファイル名に使用できない文字を除去
    $safeVendorName = preg_replace('/[\/\\\?%*:|"<>]/', '_', $vendorName);
    $date = now()->format('Ymd');
    $fileName = "精算書_{$safeVendorName}_{$date}.csv";
    
    // CSVデータを生成
    $csvData = [];
    
    // BOMを追加（Excelで文字化けしないように）
    $csvData[] = "\xEF\xBB\xBF";
    
    // ヘッダー情報行
    $csvData[] = $arrayToCsv(['委託先名', $headerInfo['vendor_name'] ?? '']);
    $csvData[] = $arrayToCsv(['請求期間', $headerInfo['billing_period'] ?? '']);
    $csvData[] = $arrayToCsv(['手数料率', ($headerInfo['commission_rate'] ?? 0) . '%']);
    $csvData[] = ''; // 空行
    
    // 集計行
    $csvData[] = $arrayToCsv(['売上合計', number_format($this->totalAmount)]);
    $csvData[] = $arrayToCsv(['手数料合計', number_format($this->totalCommission)]);
    $csvData[] = $arrayToCsv(['差引支払額（振込額）', number_format($this->totalNetAmount)]);
    $csvData[] = ''; // 空行
    
    // 明細ヘッダー
    $csvData[] = $arrayToCsv(['商品名', '数量', '単価', '金額', '手数料', '差引支払額']);
    
    // 明細データ
    foreach ($sales as $sale) {
        $csvData[] = $arrayToCsv([
            $sale->product_name,
            $sale->quantity,
            number_format($sale->unit_price),
            number_format($sale->amount),
            number_format($sale->commission),
            number_format($sale->net_amount),
        ]);
    }
    
    $csvContent = implode("\n", $csvData);
    
    // JavaScriptでダウンロードを実行
    $this->dispatch('download-csv', [
        'content' => base64_encode($csvContent),
        'filename' => $fileName,
    ]);
};

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
                        <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border-2 border-primary-200 dark:border-primary-800">
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
                <flux:button variant="primary" wire:click="export" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="export">
                        <flux:icon.arrow-down-tray variant="micro" class="mr-2" />
                        CSV出力
                    </span>
                    <span wire:loading wire:target="export">
                        出力中...
                    </span>
                </flux:button>
                <flux:button variant="ghost" wire:click="back">
                    <flux:icon.arrow-left variant="micro" class="mr-2" />
                    委託販売請求書発行画面に戻る
                </flux:button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('download-csv', (data) => {
                const content = atob(data[0].content);
                const filename = data[0].filename;
                
                const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</div>
