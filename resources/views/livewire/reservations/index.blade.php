<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                予約管理
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                ご利用になる機能を選択してください
            </p>
        </div>

        <!-- 4つの機能カード (2x2グリッド) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-5xl mx-auto">
            <!-- 1. 予約一覧（左上） -->
            <a href="{{ route('reservations.list') }}" wire:navigate class="block group">
                <div class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-blue-600 dark:text-blue-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">予約一覧</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            全ての予約を<br>一覧表示・検索
                        </p>
                    </div>
                </div>
            </a>

            <!-- 2. 新規予約作成（右上） -->
            <a href="{{ route('reservations.create') }}" wire:navigate class="block group">
                <div class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-green-500 dark:hover:border-green-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-green-600 dark:text-green-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">新規予約作成</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            新しい予約を<br>登録
                        </p>
                    </div>
                </div>
            </a>

            <!-- 3. 予約カレンダー（左下） -->
            <a href="{{ route('reservations.calendar') }}" wire:navigate class="block group">
                <div class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-purple-600 dark:text-purple-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">予約カレンダー</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            カレンダー形式で<br>予約を確認
                        </p>
                    </div>
                </div>
            </a>

            <!-- 4. 予約統計（右下） -->

            <a href="{{ route('reservations.statistics') }}" wire:navigate class="block group">
                <div class="h-full p-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border-2 border-zinc-200 dark:border-zinc-700 hover:border-orange-500 dark:hover:border-orange-500 hover:shadow-xl transition-all duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 bg-orange-100 dark:bg-orange-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-orange-600 dark:text-orange-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">予約統計</h3>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            予約状況の<br>統計・分析
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
