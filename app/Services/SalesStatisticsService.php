<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailySalesSummary;
use App\Models\MonthlySalesSummary;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SalesStatisticsService
{
    /**
     * 日別の売上金額合計を取得
     *
     * @param  int|null  $days  取得日数（nullの場合は全期間）
     * @return Collection<string, int> 日付 => 売上金額
     */
    public function getDailySalesAmount(?int $days = null): Collection
    {
        $cacheKey = 'daily_sales_amount_' . ($days ?? 'all');
        $cacheMinutes = $days === null ? 10 : 5; // 全期間は10分、期間指定は5分

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($days) {
            $query = DailySalesSummary::query()
                ->selectRaw('sale_date as date_key, SUM(total_sales_amount) as total_amount')
                ->groupBy('sale_date')
                ->orderBy('sale_date');

            if ($days !== null) {
                $startDate = Carbon::now()->subDays($days)->startOfDay();
                $query->where('sale_date', '>=', $startDate);
            }

            return $query->get()
                ->pluck('total_amount', 'date_key')
                ->map(fn($amount) => (int) $amount);
        });
    }

    /**
     * 日別の商品コード別売上を取得（上位N商品）
     *
     * @param  int|null  $days  取得日数（nullの場合は全期間）
     * @param  int  $limit  取得件数
     * @return Collection<string, Collection<string, int>> 日付 => [商品コード => 売上金額]
     */
    public function getDailySalesByProduct(?int $days = null, int $limit = 10): Collection
    {
        $cacheKey = 'daily_sales_by_product_' . ($days ?? 'all') . '_' . $limit;
        $cacheMinutes = $days === null ? 10 : 5;

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($days, $limit) {
            // まず日付範囲を取得
            $dateRange = $this->getDateRange($days);
            if (! $dateRange) {
                return collect();
            }

            [$startDate, $endDate] = $dateRange;

            // 事前集計テーブルから取得（ウィンドウ関数を使用）
            $results = DailySalesSummary::query()
                ->selectRaw('
                    date_key,
                    product_code,
                    total_amount
                ')
                ->fromSub(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->selectRaw('
                        sale_date as date_key,
                        product_code,
                        total_sales_amount as total_amount,
                        ROW_NUMBER() OVER (
                            PARTITION BY sale_date
                            ORDER BY total_sales_amount DESC
                        ) as rn
                    ')
                        ->from('daily_sales_summaries')
                        ->whereBetween('sale_date', [$startDate, $endDate]);
                }, 'ranked')
                ->whereRaw('rn <= ?', [$limit])
                ->orderBy('date_key')
                ->orderByDesc('total_amount')
                ->get();

            // 日付ごとにグループ化
            return $results->groupBy('date_key')
                ->map(function ($items) {
                    return $items->pluck('total_amount', 'product_code')
                        ->map(fn($amount) => (int) $amount);
                });
        });
    }

    /**
     * 日別のレジ別売上を取得
     *
     * @param  int|null  $days  取得日数（nullの場合は全期間）
     * @return Collection<string, Collection<string, int>> 日付 => [レジ名 => 売上金額]
     */
    public function getDailySalesByRegister(?int $days = null): Collection
    {
        $cacheKey = 'daily_sales_by_register_' . ($days ?? 'all');
        $cacheMinutes = $days === null ? 10 : 5;

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($days) {
            $query = DailySalesSummary::query()
                ->selectRaw('sale_date as date_key, register_name, SUM(total_sales_amount) as total_amount')
                ->whereNotNull('register_name')
                ->groupBy('sale_date', 'register_name')
                ->orderBy('sale_date')
                ->orderBy('register_name');

            if ($days !== null) {
                $startDate = Carbon::now()->subDays($days)->startOfDay();
                $query->where('sale_date', '>=', $startDate);
            }

            return $query->get()
                ->groupBy('date_key')
                ->map(function ($items) {
                    return $items->pluck('total_amount', 'register_name')
                        ->map(fn($amount) => (int) $amount);
                });
        });
    }

    /**
     * 月別の売上金額合計を取得
     *
     * @param  int|null  $months  取得月数（nullの場合は全期間）
     * @return Collection<string, int> 年月 => 売上金額
     */
    public function getMonthlySalesAmount(?int $months = null): Collection
    {
        $cacheKey = 'monthly_sales_amount_' . ($months ?? 'all');
        $cacheMinutes = $months === null ? 10 : 5;

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($months) {
            $query = MonthlySalesSummary::query()
                ->selectRaw('year_month as month, SUM(total_sales_amount) as total_amount')
                ->groupBy('year_month')
                ->orderBy('year_month');

            if ($months !== null) {
                $startDate = Carbon::now()->subMonths($months)->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $startMonth = $startDate->format('Y/m');
                $endMonth = $endDate->format('Y/m');
                $query->whereRaw('year_month >= ?', [$startMonth])
                    ->whereRaw('year_month <= ?', [$endMonth]);
            }

            return $query->get()
                ->pluck('total_amount', 'month')
                ->map(fn($amount) => (int) $amount);
        });
    }

    /**
     * 月別の商品コード別売上を取得（上位N商品）
     *
     * @param  int|null  $months  取得月数（nullの場合は全期間）
     * @param  int  $limit  取得件数
     * @return Collection<string, Collection<string, int>> 年月 => [商品コード => 売上金額]
     */
    public function getMonthlySalesByProduct(?int $months = null, int $limit = 10): Collection
    {
        $cacheKey = 'monthly_sales_by_product_' . ($months ?? 'all') . '_' . $limit;
        $cacheMinutes = $months === null ? 10 : 5;

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($months, $limit) {
            // まず月範囲を取得
            $dateRange = $this->getDateRangeForMonths($months);
            if (! $dateRange) {
                return collect();
            }

            [$startDate, $endDate] = $dateRange;
            $startMonth = $startDate->format('Y/m');
            $endMonth = $endDate->format('Y/m');

            // 事前集計テーブルから取得（ウィンドウ関数を使用）
            $results = MonthlySalesSummary::query()
                ->selectRaw('
                    month,
                    product_code,
                    total_amount
                ')
                ->fromSub(function ($subQuery) use ($startMonth, $endMonth) {
                    $subQuery->selectRaw('
                        year_month as month,
                        product_code,
                        total_sales_amount as total_amount,
                        ROW_NUMBER() OVER (
                            PARTITION BY year_month
                            ORDER BY total_sales_amount DESC
                        ) as rn
                    ')
                        ->from('monthly_sales_summaries')
                        ->whereRaw('year_month >= ?', [$startMonth])
                        ->whereRaw('year_month <= ?', [$endMonth]);
                }, 'ranked')
                ->whereRaw('rn <= ?', [$limit])
                ->orderBy('month')
                ->orderByDesc('total_amount')
                ->get();

            // 月ごとにグループ化
            return $results->groupBy('month')
                ->map(function ($items) {
                    return $items->pluck('total_amount', 'product_code')
                        ->map(fn($amount) => (int) $amount);
                });
        });
    }

    /**
     * 月別のレジ別売上を取得
     *
     * @param  int|null  $months  取得月数（nullの場合は全期間）
     * @return Collection<string, Collection<string, int>> 年月 => [レジ名 => 売上金額]
     */
    public function getMonthlySalesByRegister(?int $months = null): Collection
    {
        $cacheKey = 'monthly_sales_by_register_' . ($months ?? 'all');
        $cacheMinutes = $months === null ? 10 : 5;

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($months) {
            $query = MonthlySalesSummary::query()
                ->selectRaw('year_month as month, register_name, SUM(total_sales_amount) as total_amount')
                ->whereNotNull('register_name')
                ->groupBy('year_month', 'register_name')
                ->orderBy('year_month')
                ->orderBy('register_name');

            if ($months !== null) {
                $startDate = Carbon::now()->subMonths($months)->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $startMonth = $startDate->format('Y/m');
                $endMonth = $endDate->format('Y/m');
                $query->whereRaw('year_month >= ?', [$startMonth])
                    ->whereRaw('year_month <= ?', [$endMonth]);
            }

            return $query->get()
                ->groupBy('month')
                ->map(function ($items) {
                    return $items->pluck('total_amount', 'register_name')
                        ->map(fn($amount) => (int) $amount);
                });
        });
    }

    /**
     * 日付範囲の全ての日付を取得（データがない日も含める）
     *
     * @param  int|null  $days  取得日数（nullの場合は全期間）
     * @return array<string> 日付の配列
     */
    public function getAllDates(?int $days = null): array
    {
        $cacheKey = 'all_dates_' . ($days ?? 'all');
        $cacheMinutes = $days === null ? 60 : 5; // 日付リストは長期間キャッシュ可能

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($days) {
            $dateRange = $this->getDateRange($days);
            if (! $dateRange) {
                return [];
            }

            [$startDate, $endDate] = $dateRange;

            $dates = [];
            $current = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();

            while ($current->lte($end)) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }

            return $dates;
        });
    }

    /**
     * 月範囲の全ての月を取得（データがない月も含める）
     *
     * @param  int|null  $months  取得月数（nullの場合は全期間）
     * @return array<string> 年月の配列
     */
    public function getAllMonths(?int $months = null): array
    {
        $cacheKey = 'all_months_' . ($months ?? 'all');
        $cacheMinutes = $months === null ? 60 : 5; // 月リストは長期間キャッシュ可能

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($months) {
            $dateRange = $this->getDateRangeForMonths($months);
            if (! $dateRange) {
                return [];
            }

            [$startDate, $endDate] = $dateRange;

            $months = [];
            $current = Carbon::parse($startDate)->startOfMonth();
            $end = Carbon::parse($endDate)->startOfMonth();

            while ($current->lte($end)) {
                $months[] = $current->format('Y/m');
                $current->addMonth();
            }

            return $months;
        });
    }

    /**
     * 日付範囲を取得（1回のクエリでmin/maxを取得）
     *
     * @param  int|null  $days  取得日数（nullの場合は全期間）
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function getDateRange(?int $days = null): ?array
    {
        if ($days === null) {
            // 全期間の場合は事前集計テーブルからmin/maxを取得（インデックス活用）
            $dateRange = DailySalesSummary::selectRaw('MIN(sale_date) as min_date, MAX(sale_date) as max_date')
                ->first();

            if (! $dateRange || ! $dateRange->min_date || ! $dateRange->max_date) {
                return null;
            }

            $startDate = Carbon::parse($dateRange->min_date)->startOfDay();
            $endDate = Carbon::parse($dateRange->max_date)->startOfDay();
        } else {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        return [$startDate, $endDate];
    }

    /**
     * 月範囲を取得（1回のクエリでmin/maxを取得）
     *
     * @param  int|null  $months  取得月数（nullの場合は全期間）
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function getDateRangeForMonths(?int $months = null): ?array
    {
        if ($months === null) {
            // 全期間の場合は事前集計テーブルからmin/maxを取得（インデックス活用）
            $dateRange = MonthlySalesSummary::selectRaw('MIN(year_month) as min_month, MAX(year_month) as max_month')
                ->first();

            if (! $dateRange || ! $dateRange->min_month || ! $dateRange->max_month) {
                // 月別サマリーがない場合は日別サマリーから取得
                $dateRange = DailySalesSummary::selectRaw('MIN(sale_date) as min_date, MAX(sale_date) as max_date')
                    ->first();

                if (! $dateRange || ! $dateRange->min_date || ! $dateRange->max_date) {
                    return null;
                }

                $startDate = Carbon::parse($dateRange->min_date)->startOfMonth();
                $endDate = Carbon::parse($dateRange->max_date)->endOfMonth();
            } else {
                // year_month形式（Y/m）からCarbonに変換
                $startDate = Carbon::createFromFormat('Y/m', $dateRange->min_month)->startOfMonth();
                $endDate = Carbon::createFromFormat('Y/m', $dateRange->max_month)->endOfMonth();
            }
        } else {
            $startDate = Carbon::now()->subMonths($months)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        return [$startDate, $endDate];
    }

    /**
     * 指定日付に関連するキャッシュを無効化
     *
     * @param  \Carbon\Carbon|string  $date  日付
     */
    public function clearCacheForDate($date): void
    {
        try {
            $carbonDate = Carbon::parse($date);
            $yearMonth = $carbonDate->format('Y/m');

            // 日別キャッシュの無効化（全期間と期間指定）
            Cache::forget('daily_sales_amount_all');
            Cache::forget('daily_sales_by_register_all');
            Cache::forget('all_dates_all');

            // 月別キャッシュの無効化（全期間と期間指定）
            Cache::forget('monthly_sales_amount_all');
            Cache::forget('monthly_sales_by_register_all');
            Cache::forget('all_months_all');

            // 商品別キャッシュの無効化（一般的なlimit値に対応）
            $commonLimits = [5, 10, 20, 50];
            foreach ($commonLimits as $limit) {
                Cache::forget('daily_sales_by_product_all_' . $limit);
                Cache::forget('monthly_sales_by_product_all_' . $limit);
            }

            // 期間指定のキャッシュも無効化（一般的な期間）
            $commonDays = [7, 30, 90, 180, 365];
            foreach ($commonDays as $days) {
                Cache::forget('daily_sales_amount_' . $days);
                Cache::forget('daily_sales_by_register_' . $days);
                Cache::forget('all_dates_' . $days);
                foreach ($commonLimits as $limit) {
                    Cache::forget('daily_sales_by_product_' . $days . '_' . $limit);
                }
            }

            $commonMonths = [1, 3, 6, 12];
            foreach ($commonMonths as $months) {
                Cache::forget('monthly_sales_amount_' . $months);
                Cache::forget('monthly_sales_by_register_' . $months);
                Cache::forget('all_months_' . $months);
                foreach ($commonLimits as $limit) {
                    Cache::forget('monthly_sales_by_product_' . $months . '_' . $limit);
                }
            }
        } catch (\Exception $e) {
            Log::warning('キャッシュ無効化処理エラー', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
