<?php

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                POSデータ集計システム
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                ご利用になる機能を選択してください
            </p>
        </div>

        <!-- 3つの機能カード (3x1グリッド) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-6xl mx-auto">
            <!-- 1. 集計実行 -->
            <a href="{{ route('sales.aggregate') }}" wire:navigate class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-emerald-600 dark:text-emerald-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">集計実行</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            PDFファイルをアップロードして<br>商品コード別に集計
                        </p>
                    </div>
                </div>
            </a>

            <!-- 2. 集計履歴 -->
            <a href="{{ route('sales.history') }}" wire:navigate class="block group">
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
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">集計履歴</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            過去の集計履歴を<br>確認・ダウンロード
                        </p>
                    </div>
                </div>
            </a>

            <!-- 3. 売上統計 -->
            <a href="{{ route('sales.statistics') }}" wire:navigate class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-purple-600 dark:text-purple-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">売上統計</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            日別・月別の<br>売上グラフを表示
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
