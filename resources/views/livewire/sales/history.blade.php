<?php

use App\Models\SalesAggregation;
use function Livewire\Volt\with;

// 履歴一覧データを取得
with(
    fn() => [
        'aggregations' => SalesAggregation::query()->with('user')->orderBy('aggregated_at', 'desc')->paginate(20),
    ],
);

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 戻るボタン -->
        <div class="mb-6 flex items-center gap-4">
            <a href="{{ route('sales.index') }}" wire:navigate
                class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6 text-zinc-700 dark:text-zinc-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
                集計履歴
            </h1>
        </div>

        <!-- 履歴一覧テーブル -->
        <flux:card>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:columns>
                        <flux:column>集計日時</flux:column>
                        <flux:column>元のPDFファイル</flux:column>
                        <flux:column>Excelファイル名</flux:column>
                        <flux:column>集計ユーザー</flux:column>
                        <flux:column class="text-right">操作</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse ($aggregations as $aggregation)
                            <flux:row :key="$aggregation->id">
                                <flux:cell>
                                    {{ $aggregation->aggregated_at->format('Y/m/d H:i:s') }}
                                </flux:cell>
                                <flux:cell>
                                    <div class="space-y-1">
                                        @foreach ($aggregation->original_pdf_files as $pdfFile)
                                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $pdfFile }}
                                            </div>
                                        @endforeach
                                    </div>
                                </flux:cell>
                                <flux:cell>
                                    <div class="font-medium">{{ $aggregation->excel_filename }}</div>
                                </flux:cell>
                                <flux:cell>
                                    {{ $aggregation->user->name }}
                                </flux:cell>
                                <flux:cell class="text-right">
                                    @if ($aggregation->fileExists())
                                        <a href="{{ route('sales.download-history', $aggregation->id) }}" download>
                                            <flux:button size="sm" variant="primary">
                                                <flux:icon.arrow-down variant="micro" class="mr-1" />
                                                ダウンロード
                                            </flux:button>
                                        </a>
                                    @else
                                        <flux:badge color="red">ファイル不存在</flux:badge>
                                    @endif
                                </flux:cell>
                            </flux:row>
                        @empty
                            <flux:row>
                                <flux:cell colspan="5" class="text-center py-12">
                                    <div class="text-zinc-500 mb-4">
                                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium">集計履歴がまだありません</p>
                                        <p class="mt-1 text-sm text-zinc-400">POSデータ集計ページから集計を実行すると、ここに履歴が表示されます</p>
                                    </div>
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>
            </div>

            <!-- ページネーション -->
            @if ($aggregations->hasPages())
                <div class="mt-4 px-6 pb-6">
                    {{ $aggregations->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</div>
