<?php

use App\Models\BankTransferConversion;
use function Livewire\Volt\with;

// 履歴一覧データを取得
with(
    fn() => [
        'conversions' => BankTransferConversion::query()->with('user')->orderBy('converted_at', 'desc')->paginate(20),
    ],
);

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 戻るボタン -->
        <div class="mb-6 flex items-center gap-4">
            <a href="{{ route('bank-transfers.index') }}" wire:navigate
                class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6 text-zinc-700 dark:text-zinc-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
                変換履歴
            </h1>
        </div>

        <!-- 履歴一覧テーブル -->
        <flux:card>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:columns>
                        <flux:column>変換日時</flux:column>
                        <flux:column>元のファイル名</flux:column>
                        <flux:column>変換後ファイル名</flux:column>
                        <flux:column>変換ユーザー</flux:column>
                        <flux:column class="text-right">操作</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse ($conversions as $conversion)
                            <flux:row :key="$conversion->id">
                                <flux:cell>
                                    {{ $conversion->converted_at->format('Y/m/d H:i:s') }}
                                </flux:cell>
                                <flux:cell>
                                    <div class="font-medium">{{ $conversion->original_filename }}</div>
                                </flux:cell>
                                <flux:cell>
                                    <div class="font-medium">{{ $conversion->converted_filename }}</div>
                                </flux:cell>
                                <flux:cell>
                                    {{ $conversion->user->name }}
                                </flux:cell>
                                <flux:cell class="text-right">
                                    @if ($conversion->fileExists())
                                        <a href="{{ route('bank-transfers.download-history', $conversion->id) }}"
                                            download>
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
                                        <p class="mt-2 text-sm font-medium">変換履歴がまだありません</p>
                                        <p class="mt-1 text-sm text-zinc-400">全銀フォーマット変換ページから変換を実行すると、ここに履歴が表示されます</p>
                                    </div>
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>
            </div>

            <!-- ページネーション -->
            @if ($conversions->hasPages())
                <div class="mt-4 px-6 pb-6">
                    {{ $conversions->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</div>
