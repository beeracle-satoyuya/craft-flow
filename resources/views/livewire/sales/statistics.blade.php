<?php

use App\Services\SalesStatisticsService;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;

// レイアウト指定
layout('components.layouts.app');

// 状態管理
state([
    'periodType' => 'daily', // 'daily' or 'monthly'
    'periodRange' => '30', // '30', '12', or 'all'
]);

// サービスインスタンス（シングルトンとして使用）
$statisticsService = app(SalesStatisticsService::class);

// 日別データ取得（日別表示時のみ評価）
$dailySalesAmount = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'daily') {
        return collect();
    }
    $days = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getDailySalesAmount($days);
});

$dailySalesByProduct = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'daily') {
        return collect();
    }
    $days = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getDailySalesByProduct($days, 10);
});

$dailySalesByRegister = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'daily') {
        return collect();
    }
    $days = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getDailySalesByRegister($days);
});

$allDates = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'daily') {
        return [];
    }
    $days = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getAllDates($days);
});

// 月別データ取得（月別表示時のみ評価）
$monthlySalesAmount = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'monthly') {
        return collect();
    }
    $months = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getMonthlySalesAmount($months);
});

$monthlySalesByProduct = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'monthly') {
        return collect();
    }
    $months = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getMonthlySalesByProduct($months, 10);
});

$monthlySalesByRegister = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'monthly') {
        return collect();
    }
    $months = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getMonthlySalesByRegister($months);
});

$allMonths = computed(function () use ($statisticsService) {
    if ($this->periodType !== 'monthly') {
        return [];
    }
    $months = $this->periodRange !== 'all' ? (int) $this->periodRange : null;

    return $statisticsService->getAllMonths($months);
});

?>

<div>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <!-- ヘッダー -->
        <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('sales.index') }}" wire:navigate
                            class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-6 h-6 text-zinc-700 dark:text-zinc-300">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </a>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            売上統計
                        </h1>
                    </div>

                    <!-- 期間選択 -->
                    <div class="flex items-center gap-4">
                        <!-- 日別/月別切り替え -->
                        <div class="flex items-center gap-2 bg-zinc-100 dark:bg-zinc-700 rounded-lg p-1">
                            <button wire:click="$set('periodType', 'daily')"
                                class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $periodType === 'daily' ? 'bg-white dark:bg-zinc-600 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400' }}">
                                日別
                            </button>
                            <button wire:click="$set('periodType', 'monthly')"
                                class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $periodType === 'monthly' ? 'bg-white dark:bg-zinc-600 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400' }}">
                                月別
                            </button>
                        </div>

                        <!-- 期間範囲選択 -->
                        <select wire:model.live="periodRange"
                            class="px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            @if ($periodType === 'daily')
                                <option value="30">直近30日</option>
                                <option value="all">全期間</option>
                            @else
                                <option value="12">直近12ヶ月</option>
                                <option value="all">全期間</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if ($periodType === 'daily')
                <!-- 日別グラフ -->
                <div class="space-y-8">
                    <!-- 売上金額合計グラフ -->
                    <div
                        class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">日別売上金額合計</h2>
                        <div id="dailySalesAmountChart" class="w-full" style="min-height: 400px;"></div>
                    </div>

                    <!-- 商品コード別売上グラフ -->
                    <div
                        class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">商品コード別売上（上位10商品）</h2>
                        <div id="dailySalesByProductChart" class="w-full" style="min-height: 400px;"></div>
                    </div>

                    <!-- レジ別売上グラフ -->
                    <div
                        class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">レジ別売上</h2>
                        <div id="dailySalesByRegisterChart" class="w-full" style="min-height: 400px;"></div>
                    </div>
                </div>
            @else
                <!-- 月別グラフ -->
                <div class="space-y-8">
                    <!-- 売上金額合計グラフ -->
                    <div
                        class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">月別売上金額合計</h2>
                        <div id="monthlySalesAmountChart" class="w-full" style="min-height: 400px;"></div>
                    </div>

                    <!-- 商品コード別売上グラフ -->
                    <div
                        class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">商品コード別売上（上位10商品）</h2>
                        <div id="monthlySalesByProductChart" class="w-full" style="min-height: 400px;"></div>
                    </div>

                    <!-- レジ別売上グラフ -->
                    <div
                        class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">レジ別売上</h2>
                        <div id="monthlySalesByRegisterChart" class="w-full" style="min-height: 400px;"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // グラフインスタンスを保持
        let chartInstances = {};

        // Livewireコンポーネントが完全にレンダリングされた後に実行
        document.addEventListener('livewire:init', function() {
            Livewire.hook('morph.updated', ({
                component
            }) => {
                if (component.id === '{{ $this->getId() }}') {
                    // 既存のグラフを破棄
                    destroyCharts();
                    setTimeout(initCharts, 100);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // 初回読み込み時にグラフを描画
            setTimeout(initCharts, 100);
        });

        function destroyCharts() {
            Object.values(chartInstances).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            chartInstances = {};
        }

        function initCharts() {
            // ApexChartsが利用可能か確認
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts is not loaded. Please check if app.js is built correctly.');
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#4b5563';
            const gridColor = isDark ? '#374151' : '#e5e7eb';

            @if ($periodType === 'daily')
                // 日別グラフ
                const dailyDates = @json($this->allDates);
                const dailySalesAmount = @json($this->dailySalesAmount);
                const dailySalesByProduct = @json($this->dailySalesByProduct);
                const dailySalesByRegister = @json($this->dailySalesByRegister);

                // グラフ要素の存在確認
                const dailyAmountChartEl = document.querySelector("#dailySalesAmountChart");
                const dailyProductChartEl = document.querySelector("#dailySalesByProductChart");
                const dailyRegisterChartEl = document.querySelector("#dailySalesByRegisterChart");

                if (!dailyAmountChartEl || !dailyProductChartEl || !dailyRegisterChartEl) {
                    console.error('Chart elements not found');
                    return;
                }

                // 日別売上金額合計グラフ
                const dailyAmountData = dailyDates.map(date => dailySalesAmount[date] || 0);
                const dailyAmountChart = new ApexCharts(dailyAmountChartEl, {
                    series: [{
                        name: '売上金額',
                        data: dailyAmountData
                    }],
                    chart: {
                        type: 'line',
                        height: 400,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: true
                        }
                    },
                    stroke: {
                        width: 3,
                        curve: 'smooth'
                    },
                    colors: ['#3b82f6'],
                    xaxis: {
                        categories: dailyDates,
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            },
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    grid: {
                        borderColor: gridColor
                    }
                });
                dailyAmountChart.render();
                chartInstances.dailyAmount = dailyAmountChart;

                // 商品コード別売上グラフ（グループ棒グラフ）
                const productCodes = [...new Set(Object.values(dailySalesByProduct).flatMap(day => Object.keys(day)))]
                    .slice(0, 10);
                const productSeries = productCodes.map(code => ({
                    name: code,
                    data: dailyDates.map(date => dailySalesByProduct[date]?.[code] || 0)
                }));
                const dailyProductChart = new ApexCharts(dailyProductChartEl, {
                    series: productSeries,
                    chart: {
                        type: 'bar',
                        height: 400,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: true
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            borderRadius: 4
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316',
                        '#a855f7', '#ef4444'
                    ],
                    xaxis: {
                        categories: dailyDates,
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            },
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            colors: textColor
                        }
                    },
                    grid: {
                        borderColor: gridColor
                    }
                });
                dailyProductChart.render();
                chartInstances.dailyProduct = dailyProductChart;

                // レジ別売上グラフ（積み上げ棒グラフ）
                const registers = [...new Set(Object.values(dailySalesByRegister).flatMap(day => Object.keys(day)))];
                const registerSeries = registers.map(register => ({
                    name: register,
                    data: dailyDates.map(date => dailySalesByRegister[date]?.[register] || 0)
                }));
                const dailyRegisterChart = new ApexCharts(dailyRegisterChartEl, {
                    series: registerSeries,
                    chart: {
                        type: 'bar',
                        height: 400,
                        stacked: true,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: true
                        }
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
                    xaxis: {
                        categories: dailyDates,
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            },
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            colors: textColor
                        }
                    },
                    grid: {
                        borderColor: gridColor
                    }
                });
                dailyRegisterChart.render();
                chartInstances.dailyRegister = dailyRegisterChart;
            @else
                // 月別グラフ
                const monthlyMonths = @json($this->allMonths);
                const monthlySalesAmount = @json($this->monthlySalesAmount);
                const monthlySalesByProduct = @json($this->monthlySalesByProduct);
                const monthlySalesByRegister = @json($this->monthlySalesByRegister);

                // グラフ要素の存在確認
                const monthlyAmountChartEl = document.querySelector("#monthlySalesAmountChart");
                const monthlyProductChartEl = document.querySelector("#monthlySalesByProductChart");
                const monthlyRegisterChartEl = document.querySelector("#monthlySalesByRegisterChart");

                if (!monthlyAmountChartEl || !monthlyProductChartEl || !monthlyRegisterChartEl) {
                    console.error('Chart elements not found');
                    return;
                }

                // 月別売上金額合計グラフ
                const monthlyAmountData = monthlyMonths.map(month => monthlySalesAmount[month] || 0);
                const monthlyAmountChart = new ApexCharts(monthlyAmountChartEl, {
                    series: [{
                        name: '売上金額',
                        data: monthlyAmountData
                    }],
                    chart: {
                        type: 'line',
                        height: 400,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: true
                        }
                    },
                    stroke: {
                        width: 3,
                        curve: 'smooth'
                    },
                    colors: ['#3b82f6'],
                    xaxis: {
                        categories: monthlyMonths,
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            },
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    grid: {
                        borderColor: gridColor
                    }
                });
                monthlyAmountChart.render();
                chartInstances.monthlyAmount = monthlyAmountChart;

                // 商品コード別売上グラフ（グループ棒グラフ）
                const monthlyProductCodes = [...new Set(Object.values(monthlySalesByProduct).flatMap(month => Object.keys(
                    month)))].slice(0, 10);
                const monthlyProductSeries = monthlyProductCodes.map(code => ({
                    name: code,
                    data: monthlyMonths.map(month => monthlySalesByProduct[month]?.[code] || 0)
                }));
                const monthlyProductChart = new ApexCharts(monthlyProductChartEl, {
                    series: monthlyProductSeries,
                    chart: {
                        type: 'bar',
                        height: 400,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: true
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            borderRadius: 4
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316',
                        '#a855f7', '#ef4444'
                    ],
                    xaxis: {
                        categories: monthlyMonths,
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            },
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            colors: textColor
                        }
                    },
                    grid: {
                        borderColor: gridColor
                    }
                });
                monthlyProductChart.render();
                chartInstances.monthlyProduct = monthlyProductChart;

                // レジ別売上グラフ（積み上げ棒グラフ）
                const monthlyRegisters = [...new Set(Object.values(monthlySalesByRegister).flatMap(month => Object.keys(
                    month)))];
                const monthlyRegisterSeries = monthlyRegisters.map(register => ({
                    name: register,
                    data: monthlyMonths.map(month => monthlySalesByRegister[month]?.[register] || 0)
                }));
                const monthlyRegisterChart = new ApexCharts(monthlyRegisterChartEl, {
                    series: monthlyRegisterSeries,
                    chart: {
                        type: 'bar',
                        height: 400,
                        stacked: true,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: true
                        }
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
                    xaxis: {
                        categories: monthlyMonths,
                        labels: {
                            style: {
                                colors: textColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: textColor
                            },
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function(val) {
                                return '¥' + val.toLocaleString();
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            colors: textColor
                        }
                    },
                    grid: {
                        borderColor: gridColor
                    }
                });
                monthlyRegisterChart.render();
                chartInstances.monthlyRegister = monthlyRegisterChart;
            @endif
        }
    </script>
</div>
</div>
