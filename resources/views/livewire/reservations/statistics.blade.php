<?php

use function Livewire\Volt\{state, mount, computed, layout};
use App\Models\Reservation;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

// レイアウト指定
layout('components.layouts.app');

// 状態管理
state([
    'startDate' => now()->subYear()->format('Y-m-d'),
    'endDate' => now()->format('Y-m-d'),
]);

// 初期化
mount(function () {
    //
});

// 期間を更新
$updatePeriod = function () {
    $this->validate([
        'startDate' => 'required|date',
        'endDate' => 'required|date|after_or_equal:startDate',
    ]);
};

// 月毎の確定予約数を取得
$confirmedReservationsByMonth = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();
    
    return Reservation::whereBetween('reservation_datetime', [$start, $end])
        ->where('status', 'confirmed')
        ->get()
        ->groupBy(function ($reservation) {
            return Carbon::parse($reservation->reservation_datetime)->format('Y/m');
        })
        ->map(function ($group) {
            return $group->count();
        })
        ->sortKeys();
});

// 月毎のキャンセル数を取得
$canceledReservationsByMonth = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();
    
    return Reservation::whereBetween('reservation_datetime', [$start, $end])
        ->where('status', 'canceled')
        ->get()
        ->groupBy(function ($reservation) {
            return Carbon::parse($reservation->reservation_datetime)->format('Y/m');
        })
        ->map(function ($group) {
            return $group->count();
        })
        ->sortKeys();
});

// 月毎の予約申込数を取得（created_atベースで全status）
$totalReservationsByMonth = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();
    
    return Reservation::whereBetween('created_at', [$start, $end])
        ->get()
        ->groupBy(function ($reservation) {
            return Carbon::parse($reservation->created_at)->format('Y/m');
        })
        ->map(function ($group) {
            return $group->count();
        })
        ->sortKeys();
});

// 月毎のキャンセル率を取得
$cancellationRateByMonth = computed(function () {
    $totalByMonth = $this->totalReservationsByMonth;
    $canceledByMonth = $this->canceledReservationsByMonth;
    
    $rates = [];
    foreach ($totalByMonth as $month => $total) {
        $canceled = $canceledByMonth[$month] ?? 0;
        $rates[$month] = $total > 0 ? round(($canceled / $total) * 100, 2) : 0;
    }
    
    return collect($rates)->sortKeys();
});

// 予約経路の集計を取得
$reservationsBySource = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();
    
    return Reservation::whereBetween('created_at', [$start, $end])
        ->get()
        ->groupBy('source')
        ->map(function ($group) {
            return $group->count();
        })
        ->sortKeys();
});

// サマリー統計
$totalConfirmed = computed(function () {
    return $this->confirmedReservationsByMonth->sum();
});

$totalCanceled = computed(function () {
    return $this->canceledReservationsByMonth->sum();
});

$averageCancellationRate = computed(function () {
    $total = $this->totalReservationsByMonth->sum();
    $canceled = $this->totalCanceled;
    
    return $total > 0 ? round(($canceled / $total) * 100, 2) : 0;
});

// 全ての月を取得（データがない月も含める）
$allMonths = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfMonth();
    $end = Carbon::parse($this->endDate)->endOfMonth();
    
    $months = [];
    $current = $start->copy();
    
    while ($current->lte($end)) {
        $months[] = $current->format('Y/m');
        $current->addMonth();
    }
    
    return $months;
});

// PDFエクスポート
$exportPdf = function () {
    $data = [
        'startDate' => $this->startDate,
        'endDate' => $this->endDate,
        'totalConfirmed' => $this->totalConfirmed,
        'totalCanceled' => $this->totalCanceled,
        'averageCancellationRate' => $this->averageCancellationRate,
        'confirmedByMonth' => $this->confirmedReservationsByMonth,
        'canceledByMonth' => $this->canceledReservationsByMonth,
        'totalByMonth' => $this->totalReservationsByMonth,
        'cancellationRateByMonth' => $this->cancellationRateByMonth,
        'bySource' => $this->reservationsBySource,
    ];
    
    $pdf = Pdf::loadView('pdf.statistics', $data)
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'chroot' => base_path(),
        ]);
    
    return response()->streamDownload(function () use ($pdf) {
        echo $pdf->output();
    }, '予約統計_' . now()->format('Ymd_His') . '.pdf');
};

?>

<div>
    @volt('reservations.statistics')
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <!-- ヘッダー -->
        <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('reservations.index') }}" wire:navigate 
                           class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-zinc-700 dark:text-zinc-300">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </a>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            予約統計
                        </h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- 期間選択 -->
                        <div class="flex items-center gap-2">
                            <input type="date" wire:model.blur="startDate" 
                                   class="px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <span class="text-zinc-600 dark:text-zinc-400">〜</span>
                            <input type="date" wire:model.blur="endDate" 
                                   class="px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- PDFエクスポートボタン -->
                        <button wire:click="exportPdf" 
                                class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            PDFエクスポート
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- サマリーカード -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- 総確定予約数 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">総確定予約数</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->totalConfirmed) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600 dark:text-blue-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- 総キャンセル数 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">総キャンセル数</p>
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->totalCanceled) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600 dark:text-red-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- 平均キャンセル率 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">平均キャンセル率</p>
                            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $this->averageCancellationRate }}%</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600 dark:text-orange-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" wire:init="$refresh">
                <!-- 左側: 複合グラフ (棒グラフ + 折れ線グラフ) -->
                <div class="lg:col-span-2 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">月毎の予約推移</h2>
                    <div id="combinedChart" class="w-full" style="min-height: 400px;" wire:ignore></div>
                </div>

                <!-- 右側: 円グラフ (予約経路) -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">予約経路</h2>
                    <div id="sourceChart" class="w-full" style="min-height: 400px;" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
    
    <script>
        let combinedChart = null;
        let sourceChart = null;
        let chartsInitialized = false;

        // ページ読み込み完了後にグラフを初期化
        window.addEventListener('load', function() {
            setTimeout(initCharts, 500);
        });

        // Livewireのライフサイクルイベントに対応
        document.addEventListener('livewire:navigated', function() {
            setTimeout(initCharts, 500);
        });

        // データ更新時にグラフを再描画
        document.addEventListener('livewire:init', function() {
            Livewire.hook('morph.updated', ({ component }) => {
                if (component.id === '{{ $this->getId() }}') {
                    setTimeout(initCharts, 300);
                }
            });
        });

        function initCharts() {
            console.log('initCharts called');
            
            // 既存のグラフを削除
            const combinedChartEl = document.querySelector('#combinedChart');
            const sourceChartEl = document.querySelector('#sourceChart');
            
            if (!combinedChartEl || !sourceChartEl) {
                console.log('Chart elements not found', { combinedChartEl, sourceChartEl });
                return;
            }
            
            console.log('Chart elements found, initializing...');
            
            // 既存のグラフインスタンスを破棄
            if (combinedChart) {
                try {
                    combinedChart.destroy();
                } catch(e) {
                    console.log('Error destroying combinedChart', e);
                }
                combinedChart = null;
            }
            if (sourceChart) {
                try {
                    sourceChart.destroy();
                } catch(e) {
                    console.log('Error destroying sourceChart', e);
                }
                sourceChart = null;
            }
            
            combinedChartEl.innerHTML = '';
            sourceChartEl.innerHTML = '';
            // データ準備
            const months = @json($this->allMonths);
            const confirmed = @json($this->confirmedReservationsByMonth);
            const canceled = @json($this->canceledReservationsByMonth);
            const total = @json($this->totalReservationsByMonth);
            const cancellationRate = @json($this->cancellationRateByMonth);
            
            // 月毎データを配列に変換
            const confirmedData = months.map(month => confirmed[month] || 0);
            const canceledData = months.map(month => canceled[month] || 0);
            const totalData = months.map(month => total[month] || 0);
            const rateData = months.map(month => cancellationRate[month] || 0);

            // 複合グラフ (棒グラフ + 折れ線グラフ)
            const combinedOptions = {
                series: [
                    {
                        name: '確定予約数',
                        type: 'column',
                        data: confirmedData
                    },
                    {
                        name: 'キャンセル数',
                        type: 'column',
                        data: canceledData
                    },
                    {
                        name: '予約申込数',
                        type: 'line',
                        data: totalData
                    },
                    {
                        name: 'キャンセル率(%)',
                        type: 'line',
                        data: rateData
                    }
                ],
                chart: {
                    height: 400,
                    type: 'line',
                    stacked: false,
                    toolbar: {
                        show: true
                    },
                    fontFamily: 'inherit'
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: [0, 0, 3, 3],
                    curve: 'smooth'
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 4
                    }
                },
                colors: ['#3b82f6', '#ef4444', '#10b981', '#f59e0b'],
                xaxis: {
                    categories: months,
                    labels: {
                        style: {
                            colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                        }
                    }
                },
                yaxis: [
                    {
                        title: {
                            text: '予約数',
                            style: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                            }
                        },
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                            }
                        }
                    },
                    {
                        opposite: true,
                        title: {
                            text: 'キャンセル率(%)',
                            style: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                            }
                        },
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                            }
                        }
                    }
                ],
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    labels: {
                        colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                    }
                },
                grid: {
                    borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                }
            };

            combinedChart = new ApexCharts(document.querySelector("#combinedChart"), combinedOptions);
            combinedChart.render().then(() => {
                console.log('Combined chart rendered successfully');
            }).catch(e => {
                console.error('Error rendering combined chart', e);
            });

            // 円グラフ (予約経路)
            const sourceData = @json($this->reservationsBySource);
            const sourceLabels = Object.keys(sourceData).map(source => {
                const sourceMap = {
                    'web': 'Web',
                    'phone': '電話',
                    'walk-in': '直接来店',
                    'asoview': 'アソビュー',
                    'jalan': 'じゃらん'
                };
                return sourceMap[source] || source;
            });
            const sourceValues = Object.values(sourceData);

            const sourceOptions = {
                series: sourceValues,
                chart: {
                    type: 'pie',
                    height: 400,
                    fontFamily: 'inherit'
                },
                labels: sourceLabels,
                colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(1) + '%';
                    },
                    style: {
                        colors: ['#fff']
                    }
                },
                tooltip: {
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                }
            };

            sourceChart = new ApexCharts(sourceChartEl, sourceOptions);
            sourceChart.render().then(() => {
                console.log('Source chart rendered successfully');
                chartsInitialized = true;
            }).catch(e => {
                console.error('Error rendering source chart', e);
            });
        }
    </script>
    @endvolt
</div>

