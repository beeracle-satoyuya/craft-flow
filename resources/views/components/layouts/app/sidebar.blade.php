<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 relative">
        <!-- 背景画像 -->
        @php
            // ページごとに背景画像を切り替え
            $backgroundImage = 'images/background.jpg'; // デフォルト（その他のページ用）
            $isWorkshopPage = false; // 体験プログラムページかどうか
            
            // 予約管理関連ページ
            if (request()->is('dashboard/reservations*')) {
                $backgroundImage = 'images/reservations-bg.jpg';
            }
            // 体験プログラム関連ページ
            elseif (request()->is('dashboard/workshops*') || request()->is('dashboard/workshop-categories*')) {
                $backgroundImage = 'images/background-exterior.jpg';
                $isWorkshopPage = true;
            }
        @endphp
        <div class="fixed inset-0 z-0" style="background-image: url('{{ asset($backgroundImage) }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-color: #1a365d; background-attachment: fixed;">
            <!-- 暗いオーバーレイ -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/80 via-zinc-900/80 to-slate-900/80"></div>
        </div>
        
        <!-- コンテンツラッパー -->
        <div class="relative z-10">
        <!-- ヘッダーナビゲーション -->
        <header class="sticky top-0 z-50 backdrop-blur-sm border-b {{ $isWorkshopPage ? 'bg-amber-100 dark:bg-amber-900 border-amber-300 dark:border-amber-700' : 'bg-white/95 dark:bg-zinc-800/95 border-zinc-200 dark:border-zinc-700' }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- ロゴ -->
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity" wire:navigate>
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg {{ $isWorkshopPage ? 'bg-amber-700 text-white' : 'bg-indigo-600 text-white' }}">
                            <span class="text-xl font-bold">B</span>
                        </div>
                        <div>
                            <div class="text-lg font-bold {{ $isWorkshopPage ? 'text-amber-950 dark:text-amber-50' : 'text-zinc-900 dark:text-white' }}">LaravelチームB</div>
                        </div>
                    </a>

                    <!-- デスクトップメニュー（グローバルメガナビゲーション） -->
                    <nav class="hidden md:flex items-center gap-1">
                        <!-- ダッシュボード -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false" @close-other-menus.window="if ($event.detail.exceptId !== 'dashboard') open = false" data-menu-id="dashboard">
                            <button @click="open = !open; $dispatch('close-other-menus', { exceptId: 'dashboard' })" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? ($isWorkshopPage ? 'bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400') : ($isWorkshopPage ? 'text-amber-950 hover:bg-amber-200 dark:text-amber-50 dark:hover:bg-amber-800' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                ダッシュボード
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div x-show="open" 
                                 x-cloak
                                 class="absolute left-0 mt-2 w-80 rounded-lg bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 p-4">
                                <div class="grid gap-2">
                                    <a href="{{ route('dashboard') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">ダッシュボード</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">4つの機能へのハブ</div>
                                        </div>
                                    </a>
                                    
                                    <!-- 全銀フォーマット変換 -->
                                    <a href="{{ url('/dashboard/bank-transfers') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">全銀フォーマット変換</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">全銀フォーマットに変換</div>
                                        </div>
                                    </a>
                                    
                                    <!-- POSレジデータ集計 -->
                                    <a href="{{ url('/dashboard/sales') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">POSレジデータ集計</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">売上データを集計・分析</div>
                                        </div>
                                    </a>
                                    
                                    <!-- 委託販売精算書 -->
                                    <a href="{{ url('/dashboard/consignment-sales') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">委託販売精算書</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">委託販売の精算書を作成</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 予約管理メガメニュー -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false" @close-other-menus.window="if ($event.detail.exceptId !== 'reservations') open = false" data-menu-id="reservations">
                            <button @click="open = !open; $dispatch('close-other-menus', { exceptId: 'reservations' })" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('reservations.*') ? ($isWorkshopPage ? 'bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400') : ($isWorkshopPage ? 'text-amber-950 hover:bg-amber-200 dark:text-amber-50 dark:hover:bg-amber-800' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                予約管理
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div x-show="open" 
                                 x-cloak
                                 class="absolute left-0 mt-2 w-96 rounded-lg bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 p-4">
                                <div class="grid gap-2">
                                    <a href="{{ route('reservations.index') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">予約管理ダッシュボード</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">予約機能の4つのハブ</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('reservations.list') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">予約一覧</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">全ての予約を確認・検索</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('reservations.create') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">新規予約登録</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">新しい予約を作成</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('reservations.calendar') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">予約カレンダー</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">カレンダー形式で予約を表示</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('reservations.statistics') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">予約統計</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">統計データとグラフを表示</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 体験プログラムメガメニュー -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false" @close-other-menus.window="if ($event.detail.exceptId !== 'programs') open = false" data-menu-id="programs">
                            <button @click="open = !open; $dispatch('close-other-menus', { exceptId: 'programs' })" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('workshops.*') ? ($isWorkshopPage ? 'bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400') : ($isWorkshopPage ? 'text-amber-950 hover:bg-amber-200 dark:text-amber-50 dark:hover:bg-amber-800' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                </svg>
                                体験プログラム
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div x-show="open" 
                                 x-cloak
                                 class="absolute left-0 mt-2 w-96 rounded-lg bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 p-4">
                                <div class="grid gap-2">
                                    <a href="{{ route('workshops.index') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">プログラム一覧</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">全てのプログラムを管理</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('workshops.create') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">新規プログラム作成</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">新しいプログラムを追加</div>
                                        </div>
                                    </a>
                                    <a href="{{ route('workshop-categories.index') }}" 
                                       @click="open = false"
                                       class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                       wire:navigate>
                                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">カテゴリ管理</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">プログラムのカテゴリを整理</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <!-- 右側メニュー（常時展開型グローバルナビゲーション） -->
                    <div class="flex items-center gap-1">
                        <!-- ユーザー情報表示 -->
                        <div class="flex items-center gap-2 px-3 py-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $isWorkshopPage ? 'bg-amber-700 text-white' : 'bg-indigo-600 text-white' }} text-sm font-medium">
                                {{ auth()->user()->initials() }}
                            </div>
                            <div class="hidden lg:block">
                                <div class="text-sm font-medium {{ $isWorkshopPage ? 'text-amber-950 dark:text-amber-50' : 'text-zinc-900 dark:text-white' }}">{{ auth()->user()->name }}</div>
                            </div>
                        </div>

                        <!-- 区切り線 -->
                        <div class="hidden md:block h-6 w-px {{ $isWorkshopPage ? 'bg-amber-400 dark:bg-amber-600' : 'bg-zinc-300 dark:bg-zinc-600' }}"></div>

                        <!-- 設定リンク -->
                        <a href="{{ route('profile.edit') }}" 
                           wire:navigate
                           class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ $isWorkshopPage ? 'text-amber-950 hover:bg-amber-200 dark:text-amber-50 dark:hover:bg-amber-800' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700' }} transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <span class="hidden lg:inline">設定</span>
                        </a>

                        <!-- 区切り線 -->
                        <div class="hidden md:block h-6 w-px {{ $isWorkshopPage ? 'bg-amber-400 dark:bg-amber-600' : 'bg-zinc-300 dark:bg-zinc-600' }}"></div>

                        <!-- ログアウトボタン -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                            <button type="submit" 
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ $isWorkshopPage ? 'text-amber-950 hover:bg-amber-200 dark:text-amber-50 dark:hover:bg-amber-800' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700' }} transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                                <span class="hidden lg:inline">ログアウト</span>
                            </button>
                    </form>

                        <!-- モバイルメニュートグル -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" 
                                class="md:hidden p-2 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 ml-2"
                                x-data="{ mobileMenuOpen: false }">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- モバイルメニュー -->
            <div x-data="{ mobileMenuOpen: false }" 
                 x-show="mobileMenuOpen" 
                 @click.outside="mobileMenuOpen = false"
                 x-cloak
                 class="md:hidden border-t border-zinc-200 dark:border-zinc-700">
                <div class="px-4 py-3 space-y-1">
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-zinc-700 hover:bg-zinc-100' }}"
                       wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        ダッシュボード
                    </a>
                    <a href="{{ route('reservations.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('reservations.*') ? 'bg-indigo-50 text-indigo-600' : 'text-zinc-700 hover:bg-zinc-100' }}"
                       wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        予約管理
                    </a>
                    <a href="{{ route('workshops.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('workshops.*') ? 'bg-indigo-50 text-indigo-600' : 'text-zinc-700 hover:bg-zinc-100' }}"
                       wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                        </svg>
                        体験プログラム
                    </a>
                    <a href="{{ route('workshop-categories.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('workshop-categories.*') ? 'bg-indigo-50 text-indigo-600' : 'text-zinc-700 hover:bg-zinc-100' }}"
                       wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                        カテゴリ管理
                    </a>
                </div>
            </div>
        </header>

        {{ $slot }}

        @fluxScripts
        </div>
        <!-- /コンテンツラッパー -->
    </body>
</html>
