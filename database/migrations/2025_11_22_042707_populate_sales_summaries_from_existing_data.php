<?php

use App\Models\DailySalesSummary;
use App\Models\MonthlySalesSummary;
use App\Models\SalesAggregationItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 日別サマリーの作成
        $dailySummaries = SalesAggregationItem::query()
            ->selectRaw('
                sale_date,
                product_code,
                MAX(product_name) as product_name,
                SUM(sales_amount) as total_sales_amount,
                SUM(quantity) as total_quantity,
                register_name
            ')
            ->groupBy('sale_date', 'product_code', 'register_name')
            ->get();

        foreach ($dailySummaries as $summary) {
            DailySalesSummary::updateOrCreate(
                [
                    'sale_date' => $summary->sale_date,
                    'product_code' => $summary->product_code,
                    'register_name' => $summary->register_name,
                ],
                [
                    'product_name' => $summary->product_name,
                    'total_sales_amount' => (int) $summary->total_sales_amount,
                    'total_quantity' => (int) $summary->total_quantity,
                ]
            );
        }

        // 月別サマリーの作成
        $monthlySummaries = SalesAggregationItem::query()
            ->selectRaw('
                DATE_FORMAT(sale_date, "%Y/%m") as year_month,
                product_code,
                MAX(product_name) as product_name,
                SUM(sales_amount) as total_sales_amount,
                SUM(quantity) as total_quantity,
                register_name
            ')
            ->groupByRaw('DATE_FORMAT(sale_date, "%Y/%m"), product_code, register_name')
            ->get();

        foreach ($monthlySummaries as $summary) {
            MonthlySalesSummary::updateOrCreate(
                [
                    'year_month' => $summary->year_month,
                    'product_code' => $summary->product_code,
                    'register_name' => $summary->register_name,
                ],
                [
                    'product_name' => $summary->product_name,
                    'total_sales_amount' => (int) $summary->total_sales_amount,
                    'total_quantity' => (int) $summary->total_quantity,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('daily_sales_summaries')->truncate();
        DB::table('monthly_sales_summaries')->truncate();
    }
};
