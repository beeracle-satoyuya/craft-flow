<?php

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                全銀フォーマット変換
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                ご利用になる機能を選択してください
            </p>
        </div>

        <!-- 2つの機能カード (2x1グリッド) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-5xl mx-auto">
            <!-- 1. 全銀フォーマット変換 -->
            <a href="{{ route('bank-transfers.convert') }}" wire:navigate class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-emerald-600 dark:text-emerald-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">全銀フォーマット変換</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            Excelファイルをアップロードして<br>全銀フォーマットに変換
                        </p>
                    </div>
                </div>
            </a>

            <!-- 2. 変換履歴 -->
            <a href="{{ route('bank-transfers.history') }}" wire:navigate class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-blue-600 dark:text-blue-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">変換履歴</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            過去の変換履歴を<br>確認・ダウンロード
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
