<x-layouts.app :title="__('Dashboard')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                盛岡手づくり村 管理システム
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                ご利用になる機能を選択してください
            </p>
        </div>

        <!-- 4つの機能カード (2x2グリッド) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-5xl mx-auto">
            <!-- 1. 全銀フォーマット変換 -->
            <a href="#" class="block group">
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
                            銀行振込データを<br>全銀フォーマットに変換
                        </p>
                        {{-- <span class="mt-3 text-xs text-zinc-500 dark:text-zinc-500">準備中</span> --}}
                    </div>
                </div>
            </a>

            <a href="#" class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-amber-500 dark:hover:border-amber-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-amber-600 dark:text-amber-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">POSレジデータ集計</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            レジの売上データを<br>集計・分析
                        </p>
                        {{-- <span class="mt-3 text-xs text-zinc-500 dark:text-zinc-500">準備中</span> --}}
                    </div>
                </div>
            </a>

            <!-- 3. 委託販売請求書発行 -->
            <a href="{{ route('consignment-sales.index') }}" wire:navigate class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-purple-600 dark:text-purple-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">委託販売精算書発行</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            委託販売の精算書を<br>作成・発行
                        </p>
                    </div>
                </div>
            </a>

            <!-- 4. 予約管理（右下） -->
            <a href="{{ route('reservations.index') }}" wire:navigate class="block group">
                <div
                    class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-indigo-500 dark:hover:border-indigo-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-20 h-20 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-10 h-10 text-indigo-600 dark:text-indigo-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">予約管理</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            体験プログラムの予約を<br>登録・管理できます
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-layouts.app>
