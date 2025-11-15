<?php

use App\Models\ConsignmentSale;
use Illuminate\Support\Collection;

use function Livewire\Volt\computed;

// データベースから委託販売データを取得して集計
$products = computed(function () {
    // 最新のデータを取得（削除されていないもの）
    $sales = ConsignmentSale::query()
        ->orderBy('created_at', 'desc')
        ->get();

    // 商品名ごとに集計
    $grouped = $sales->groupBy('product_name')->map(function (Collection $items) {
        return [
            'product_name' => $items->first()->product_name,
            'total_quantity' => $items->sum('quantity'),
            'total_amount' => $items->sum('amount'),
        ];
    })->values();

    return $grouped;
});

// 合計金額を計算
$totalAmount = computed(function () {
    return ConsignmentSale::query()->sum('amount');
});

// 戻るボタンの処理
$back = function () {
    return $this->redirect(route('consignment-sales.index'), navigate: true);
};

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                精算書
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                委託販売の精算結果を表示します
            </p>
        </div>

        <!-- 合計金額表示 -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <div class="text-center">
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                            合計金額
                        </div>
                        <div class="text-5xl font-bold text-zinc-900 dark:text-white">
                            ¥{{ number_format($this->totalAmount) }}
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

                    @if ($this->products->count() > 0)
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:columns>
                                    <flux:column>商品名</flux:column>
                                    <flux:column class="text-right">販売数量合計</flux:column>
                                </flux:columns>

                                <flux:rows>
                                    @foreach ($this->products as $product)
                                        <flux:row>
                                            <flux:cell>
                                                {{ $product['product_name'] }}
                                            </flux:cell>
                                            <flux:cell class="text-right">
                                                {{ number_format($product['total_quantity']) }}
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

        <!-- 戻るボタン -->
        <div class="max-w-3xl mx-auto">
            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="back">
                    <flux:icon.arrow-left variant="micro" class="mr-2" />
                    委託販売請求書発行画面に戻る
                </flux:button>
            </div>
        </div>
    </div>
</div>

