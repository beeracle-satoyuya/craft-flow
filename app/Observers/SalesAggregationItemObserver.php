<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DailySalesSummary;
use App\Models\MonthlySalesSummary;
use App\Models\SalesAggregationItem;
use App\Services\SalesStatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesAggregationItemObserver
{
    /**
     * Observerを一時的に無効化するフラグ
     */
    private static bool $disabled = false;

    /**
     * Observerを無効化
     */
    public static function disable(): void
    {
        self::$disabled = true;
    }

    /**
     * Observerを有効化
     */
    public static function enable(): void
    {
        self::$disabled = false;
    }

    /**
     * Observerが無効化されているか確認
     */
    public static function isDisabled(): bool
    {
        return self::$disabled;
    }

    /**
     * Handle the SalesAggregationItem "created" event.
     */
    public function created(SalesAggregationItem $item): void
    {
        if (self::$disabled) {
            return;
        }

        $this->updateSummaries($item);
    }

    /**
     * Handle the SalesAggregationItem "updated" event.
     */
    public function updated(SalesAggregationItem $item): void
    {
        if (self::$disabled) {
            return;
        }

        $this->updateSummaries($item);
    }

    /**
     * Handle the SalesAggregationItem "deleted" event.
     */
    public function deleted(SalesAggregationItem $item): void
    {
        if (self::$disabled) {
            return;
        }

        $this->updateSummaries($item);
    }

    /**
     * Handle the SalesAggregationItem "restored" event.
     */
    public function restored(SalesAggregationItem $item): void
    {
        if (self::$disabled) {
            return;
        }

        $this->updateSummaries($item);
    }

    /**
     * Handle the SalesAggregationItem "force deleted" event.
     */
    public function forceDeleted(SalesAggregationItem $item): void
    {
        if (self::$disabled) {
            return;
        }

        $this->updateSummaries($item);
    }

    /**
     * バッチ処理後の一括再集計を実行
     *
     * @param  array<string>  $affectedDates  影響を受けた日付の配列
     */
    public static function batchUpdateSummaries(array $affectedDates): void
    {
        try {
            DB::transaction(function () use ($affectedDates) {
                // 日別サマリーの一括更新
                foreach ($affectedDates as $date) {
                    self::batchUpdateDailySummary($date);
                }

                // 月別サマリーの一括更新（重複する月を取得）
                $affectedMonths = array_unique(
                    array_map(fn($date) => Carbon::parse($date)->format('Y/m'), $affectedDates)
                );
                foreach ($affectedMonths as $yearMonth) {
                    self::batchUpdateMonthlySummary($yearMonth);
                }
            });

            // キャッシュを一括無効化
            $statisticsService = app(SalesStatisticsService::class);
            foreach ($affectedDates as $date) {
                $statisticsService->clearCacheForDate($date);
            }
        } catch (\Exception $e) {
            Log::error('バッチサマリー更新エラー', [
                'affected_dates' => $affectedDates,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 指定日付の日別サマリーを一括更新
     */
    private static function batchUpdateDailySummary(string $date): void
    {
        // 該当日付の全商品・レジ組み合わせを取得して再集計
        $summaries = SalesAggregationItem::query()
            ->where('sale_date', $date)
            ->selectRaw('
                sale_date,
                product_code,
                register_name,
                SUM(sales_amount) as total_sales_amount,
                SUM(quantity) as total_quantity,
                MAX(product_name) as product_name
            ')
            ->groupBy('sale_date', 'product_code', 'register_name')
            ->get();

        // 既存のサマリーを削除
        DailySalesSummary::where('sale_date', $date)->delete();

        // 新しいサマリーを一括挿入
        if ($summaries->isNotEmpty()) {
            $insertData = $summaries->map(function ($summary) {
                return [
                    'sale_date' => $summary->sale_date,
                    'product_code' => $summary->product_code,
                    'product_name' => $summary->product_name,
                    'total_sales_amount' => (int) $summary->total_sales_amount,
                    'total_quantity' => (int) $summary->total_quantity,
                    'register_name' => $summary->register_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DailySalesSummary::insert($insertData);
        }
    }

    /**
     * 指定年月の月別サマリーを一括更新
     */
    private static function batchUpdateMonthlySummary(string $yearMonth): void
    {
        // 該当年月の全商品・レジ組み合わせを取得して再集計
        $summaries = SalesAggregationItem::query()
            ->whereRaw('DATE_FORMAT(sale_date, "%Y/%m") = ?', [$yearMonth])
            ->selectRaw('
                DATE_FORMAT(sale_date, "%Y/%m") as year_month,
                product_code,
                register_name,
                SUM(sales_amount) as total_sales_amount,
                SUM(quantity) as total_quantity,
                MAX(product_name) as product_name
            ')
            ->groupBy('year_month', 'product_code', 'register_name')
            ->get();

        // 既存のサマリーを削除
        MonthlySalesSummary::where('year_month', $yearMonth)->delete();

        // 新しいサマリーを一括挿入
        if ($summaries->isNotEmpty()) {
            $insertData = $summaries->map(function ($summary) use ($yearMonth) {
                return [
                    'year_month' => $yearMonth,
                    'product_code' => $summary->product_code,
                    'product_name' => $summary->product_name,
                    'total_sales_amount' => (int) $summary->total_sales_amount,
                    'total_quantity' => (int) $summary->total_quantity,
                    'register_name' => $summary->register_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            MonthlySalesSummary::insert($insertData);
        }
    }

    /**
     * 日別・月別サマリーを更新（トランザクション内で実行）
     */
    private function updateSummaries(SalesAggregationItem $item): void
    {
        try {
            DB::transaction(function () use ($item) {
                $this->updateDailySummary($item);
                $this->updateMonthlySummary($item);
            });

            // サマリー更新後、関連するキャッシュを無効化
            $this->clearRelatedCache($item);
        } catch (\Exception $e) {
            Log::error('売上サマリー更新エラー', [
                'item_id' => $item->id,
                'sale_date' => $item->sale_date,
                'product_code' => $item->product_code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // エラーが発生しても処理は継続（Observerは例外を投げない）
        }
    }

    /**
     * 日別サマリーを更新
     */
    private function updateDailySummary(SalesAggregationItem $item): void
    {
        try {
            $query = SalesAggregationItem::query()
                ->where('sale_date', $item->sale_date)
                ->where('product_code', $item->product_code);

            // register_nameがnullの場合はwhereNullを使用
            if ($item->register_name === null) {
                $query->whereNull('register_name');
            } else {
                $query->where('register_name', $item->register_name);
            }

            $summary = $query->selectRaw('
                    SUM(sales_amount) as total_sales_amount,
                    SUM(quantity) as total_quantity,
                    MAX(product_name) as product_name
                ')
                ->first();

            if ($summary && $summary->total_sales_amount > 0) {
                $conditions = [
                    'sale_date' => $item->sale_date,
                    'product_code' => $item->product_code,
                ];

                // register_nameがnullの場合はwhereNullを使用
                if ($item->register_name === null) {
                    DailySalesSummary::updateOrCreate(
                        $conditions + ['register_name' => null],
                        [
                            'product_name' => $summary->product_name,
                            'total_sales_amount' => (int) $summary->total_sales_amount,
                            'total_quantity' => (int) $summary->total_quantity,
                        ]
                    );
                } else {
                    DailySalesSummary::updateOrCreate(
                        $conditions + ['register_name' => $item->register_name],
                        [
                            'product_name' => $summary->product_name,
                            'total_sales_amount' => (int) $summary->total_sales_amount,
                            'total_quantity' => (int) $summary->total_quantity,
                        ]
                    );
                }
            } else {
                // データが存在しない場合は削除
                $deleteQuery = DailySalesSummary::where('sale_date', $item->sale_date)
                    ->where('product_code', $item->product_code);

                // register_nameがnullの場合はwhereNullを使用
                if ($item->register_name === null) {
                    $deleteQuery->whereNull('register_name');
                } else {
                    $deleteQuery->where('register_name', $item->register_name);
                }

                $deleteQuery->delete();
            }
        } catch (\Exception $e) {
            Log::error('日別サマリー更新エラー', [
                'item_id' => $item->id,
                'sale_date' => $item->sale_date,
                'product_code' => $item->product_code,
                'register_name' => $item->register_name,
                'error' => $e->getMessage(),
            ]);
            throw $e; // トランザクションロールバックのため再スロー
        }
    }

    /**
     * 月別サマリーを更新
     */
    private function updateMonthlySummary(SalesAggregationItem $item): void
    {
        try {
            $yearMonth = Carbon::parse($item->sale_date)->format('Y/m');

            $query = SalesAggregationItem::query()
                ->whereRaw('DATE_FORMAT(sale_date, "%Y/%m") = ?', [$yearMonth])
                ->where('product_code', $item->product_code);

            // register_nameがnullの場合はwhereNullを使用
            if ($item->register_name === null) {
                $query->whereNull('register_name');
            } else {
                $query->where('register_name', $item->register_name);
            }

            $summary = $query->selectRaw('
                    SUM(sales_amount) as total_sales_amount,
                    SUM(quantity) as total_quantity,
                    MAX(product_name) as product_name
                ')
                ->first();

            if ($summary && $summary->total_sales_amount > 0) {
                $conditions = [
                    'year_month' => $yearMonth,
                    'product_code' => $item->product_code,
                ];

                // register_nameがnullの場合はwhereNullを使用
                if ($item->register_name === null) {
                    MonthlySalesSummary::updateOrCreate(
                        $conditions + ['register_name' => null],
                        [
                            'product_name' => $summary->product_name,
                            'total_sales_amount' => (int) $summary->total_sales_amount,
                            'total_quantity' => (int) $summary->total_quantity,
                        ]
                    );
                } else {
                    MonthlySalesSummary::updateOrCreate(
                        $conditions + ['register_name' => $item->register_name],
                        [
                            'product_name' => $summary->product_name,
                            'total_sales_amount' => (int) $summary->total_sales_amount,
                            'total_quantity' => (int) $summary->total_quantity,
                        ]
                    );
                }
            } else {
                $deleteQuery = MonthlySalesSummary::where('year_month', $yearMonth)
                    ->where('product_code', $item->product_code);

                // register_nameがnullの場合はwhereNullを使用
                if ($item->register_name === null) {
                    $deleteQuery->whereNull('register_name');
                } else {
                    $deleteQuery->where('register_name', $item->register_name);
                }

                $deleteQuery->delete();
            }
        } catch (\Exception $e) {
            Log::error('月別サマリー更新エラー', [
                'item_id' => $item->id,
                'sale_date' => $item->sale_date,
                'product_code' => $item->product_code,
                'register_name' => $item->register_name,
                'error' => $e->getMessage(),
            ]);
            throw $e; // トランザクションロールバックのため再スロー
        }
    }

    /**
     * 関連するキャッシュを無効化
     */
    private function clearRelatedCache(SalesAggregationItem $item): void
    {
        try {
            $statisticsService = app(SalesStatisticsService::class);
            $statisticsService->clearCacheForDate($item->sale_date);
        } catch (\Exception $e) {
            // キャッシュクリアの失敗はログに記録するが、処理は継続
            Log::warning('キャッシュ無効化エラー', [
                'item_id' => $item->id,
                'sale_date' => $item->sale_date,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
