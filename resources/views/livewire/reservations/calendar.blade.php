<?php

use function Livewire\Volt\{state, mount, computed, layout};
use App\Models\Reservation;
use Carbon\Carbon;

// レイアウト指定
layout('components.layouts.app');

// 状態管理
state([
    'currentYear' => now()->year,
    'currentMonth' => now()->month,
]);

// 初期化
mount(function () {
    //
});

// 前月に移動
$previousMonth = function () {
    $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
    $this->currentYear = $date->year;
    $this->currentMonth = $date->month;
};

// 次月に移動
$nextMonth = function () {
    $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
    $this->currentYear = $date->year;
    $this->currentMonth = $date->month;
};

// 今月に戻る
$goToday = function () {
    $this->currentYear = now()->year;
    $this->currentMonth = now()->month;
};

// 現在の月の予約データを取得
$reservations = computed(function () {
    return Reservation::with(['workshop', 'staff'])
        ->whereYear('reservation_datetime', $this->currentYear)
        ->whereMonth('reservation_datetime', $this->currentMonth)
        ->where('status', '!=', 'canceled')
        ->orderBy('reservation_datetime')
        ->get()
        ->groupBy(function ($reservation) {
            return Carbon::parse($reservation->reservation_datetime)->format('Y-m-d');
        });
});

// カレンダーの日付データを生成
$calendarDays = computed(function () {
    $firstDay = Carbon::create($this->currentYear, $this->currentMonth, 1);
    $lastDay = $firstDay->copy()->endOfMonth();
    $startDayOfWeek = $firstDay->dayOfWeek; // 0 = 日曜日
    
    $days = [];
    
    // 前月の日付で埋める
    if ($startDayOfWeek > 0) {
        $prevMonth = $firstDay->copy()->subMonth();
        $prevMonthLastDay = $prevMonth->endOfMonth()->day;
        
        for ($i = $startDayOfWeek - 1; $i >= 0; $i--) {
            $days[] = [
                'date' => $prevMonthLastDay - $i,
                'isCurrentMonth' => false,
                'isPrevMonth' => true,
                'fullDate' => $prevMonth->copy()->day($prevMonthLastDay - $i)->format('Y-m-d'),
            ];
        }
    }
    
    // 当月の日付
    for ($day = 1; $day <= $lastDay->day; $day++) {
        $date = Carbon::create($this->currentYear, $this->currentMonth, $day);
        $days[] = [
            'date' => $day,
            'isCurrentMonth' => true,
            'isPrevMonth' => false,
            'isNextMonth' => false,
            'isToday' => $date->isToday(),
            'fullDate' => $date->format('Y-m-d'),
        ];
    }
    
    // 次月の日付で埋める
    $remainingDays = 42 - count($days);
    if ($remainingDays > 0) {
        for ($day = 1; $day <= $remainingDays; $day++) {
            $nextMonth = $firstDay->copy()->addMonth();
            $days[] = [
                'date' => $day,
                'isCurrentMonth' => false,
                'isNextMonth' => true,
                'fullDate' => $nextMonth->copy()->day($day)->format('Y-m-d'),
            ];
        }
    }
    
    return $days;
});

// 日付の予約情報を取得
$getReservationsForDate = function ($date) {
    $reservations = $this->reservations;
    return $reservations[$date] ?? collect();
};

// Googleカレンダー風のカラーパレット
$getEventColor = function ($index) {
    $colors = [
        ['bg' => 'bg-[#039be5]', 'text' => 'text-white'],  // 青
        ['bg' => 'bg-[#7986cb]', 'text' => 'text-white'],  // 紫
        ['bg' => 'bg-[#33b679]', 'text' => 'text-white'],  // 緑
        ['bg' => 'bg-[#f6bf26]', 'text' => 'text-white'],  // 黄
        ['bg' => 'bg-[#e67c73]', 'text' => 'text-white'],  // 赤
        ['bg' => 'bg-[#f4511e]', 'text' => 'text-white'],  // オレンジ
        ['bg' => 'bg-[#ad1457]', 'text' => 'text-white'],  // ピンク
        ['bg' => 'bg-[#8e24aa]', 'text' => 'text-white'],  // 紫
    ];
    return $colors[$index % count($colors)];
};

?>

<div>
    @volt('reservations.calendar')
    <div class="h-screen flex bg-white dark:bg-zinc-900">
        <!-- 左サイドバー -->
        <div class="w-64 border-r border-zinc-200 dark:border-zinc-700 flex flex-col">
            <!-- サイドバーヘッダー -->
            <div class="p-4">
                <a href="{{ route('reservations.index') }}" wire:navigate 
                   class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span class="text-sm">戻る</span>
                </a>
                
                <button onclick="window.location.href='{{ route('reservations.create') }}'"
                        class="flex items-center gap-3 px-6 py-3 rounded-full shadow-md hover:shadow-lg transition-shadow bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-zinc-700 dark:text-zinc-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">作成</span>
                </button>
            </div>
            
            <!-- ミニカレンダー（簡略版） -->
            <div class="px-4 py-2">
                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    {{ $currentYear }}年{{ $currentMonth }}月
                </div>
                <div class="grid grid-cols-7 gap-1 text-xs text-center">
                    @foreach(['日', '月', '火', '水', '木', '金', '土'] as $day)
                        <div class="text-zinc-500 dark:text-zinc-400">{{ $day }}</div>
                    @endforeach
                </div>
            </div>
            
            <!-- カレンダーリスト -->
            <div class="flex-1 px-4 py-4">
                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">マイカレンダー</div>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-600"></div>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">予約カレンダー</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- メインカレンダーエリア -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- ヘッダー -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-normal text-zinc-700 dark:text-zinc-300">
                        {{ $currentYear }}年 {{ $currentMonth }}月
                    </h1>
                </div>
                
                <div class="flex items-center gap-2">
                    <button wire:click="goToday" 
                            class="px-5 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        今日
                    </button>
                    <button wire:click="previousMonth" 
                            class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-zinc-600 dark:text-zinc-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <button wire:click="nextMonth" 
                            class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-zinc-600 dark:text-zinc-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- カレンダー本体 -->
            <div class="flex-1 overflow-auto bg-white dark:bg-zinc-900">
                <div class="h-full">
                    <!-- 曜日ヘッダー -->
                    <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 bg-white dark:bg-zinc-900 z-10">
                        @foreach(['日', '月', '火', '水', '木', '金', '土'] as $index => $dayName)
                            <div class="py-2 text-center border-r border-zinc-100 dark:border-zinc-800 last:border-r-0">
                                <span class="text-xs font-medium {{ $index === 0 ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                    {{ $dayName }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- カレンダーグリッド -->
                    <div class="grid grid-cols-7" style="height: calc(100vh - 200px);">
                        @foreach($this->calendarDays as $dayIndex => $day)
                            @php
                                $dayReservations = $this->getReservationsForDate($day['fullDate']);
                                $reservationCount = $dayReservations->count();
                                $dayOfWeek = $dayIndex % 7; // 0=日曜, 6=土曜
                            @endphp
                            
                            <div class="relative border-r border-b border-zinc-200 dark:border-zinc-700 p-2 {{ $day['isCurrentMonth'] ? 'bg-white dark:bg-zinc-900' : 'bg-zinc-50 dark:bg-zinc-800/30' }} group hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer overflow-hidden">
                                
                                <!-- 日付番号 -->
                                <div class="flex items-start justify-between mb-2 px-1">
                                    @if(isset($day['isToday']) && $day['isToday'])
                                        <div class="flex items-center justify-center min-w-[28px] h-[28px] rounded-full bg-blue-600">
                                            <span class="text-sm font-semibold text-white">{{ $day['date'] }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm {{ $day['isCurrentMonth'] ? ($dayOfWeek === 0 ? 'text-red-600 dark:text-red-400' : ($dayOfWeek === 6 ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-zinc-100')) : 'text-zinc-400 dark:text-zinc-600' }} font-normal">
                                            {{ $day['date'] }}
                                        </span>
                                    @endif
                                    
                                    <!-- ホバー時の+ボタン -->
                                    @if($day['isCurrentMonth'])
                                        <button onclick="window.location.href='{{ route('reservations.create') }}?date={{ $day['fullDate'] }}'"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-zinc-600 dark:text-zinc-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                
                                <!-- イベントバー（Googleカレンダー風） -->
                                @if($reservationCount > 0 && $day['isCurrentMonth'])
                                    <div class="space-y-1">
                                        @foreach($dayReservations->take(3) as $index => $reservation)
                                            @php
                                                $color = $this->getEventColor($reservation->workshop->program_id);
                                            @endphp
                                            <a href="{{ route('reservations.show', $reservation) }}" 
                                               wire:navigate
                                               class="block px-2 py-1 rounded {{ $color['bg'] }} {{ $color['text'] }} hover:opacity-90 transition-opacity cursor-pointer text-xs font-medium truncate"
                                               title="{{ \Carbon\Carbon::parse($reservation->reservation_datetime)->format('H:i') }} - {{ $reservation->workshop->program_name }} ({{ $reservation->num_people }}名)">
                                                {{ \Carbon\Carbon::parse($reservation->reservation_datetime)->format('H:i') }} {{ $reservation->workshop->program_name }}
                                            </a>
                                        @endforeach
                                        
                                        @if($reservationCount > 3)
                                            <button class="text-xs text-zinc-600 dark:text-zinc-400 hover:underline font-normal px-2">
                                                他 {{ $reservationCount - 3 }}件
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endvolt
</div>
