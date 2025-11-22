<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

class SalesAggregationService
{
    /**
     * 複数のレジの売上レコードを商品コード別に集計する
     *
     * @param  array<int, array{date: string, records: array<int, array{product_code: string, product_name: string, unit_price: int, quantity: int, subtotal: int}>}>  $salesDataArray 各レジの売上データ配列
     * @return Collection<int, array{date: string, product_code: string, product_name: string, unit_price: int, total_quantity: int, total_sales: int}>
     */
    public function aggregate(array $salesDataArray): Collection
    {
        $aggregated = [];

        foreach ($salesDataArray as $salesData) {
            $date = $salesData['date'];
            $records = $salesData['records'];

            foreach ($records as $record) {
                $productCode = $record['product_code'];

                if (! isset($aggregated[$productCode])) {
                    // 最初に見つかったレコードの商品名と単価をマスター情報として採用
                    $aggregated[$productCode] = [
                        'date' => $date,
                        'product_code' => $productCode,
                        'product_name' => $record['product_name'],
                        'unit_price' => $record['unit_price'],
                        'total_quantity' => 0,
                        'total_sales' => 0,
                    ];
                }

                // 数量と小計を合算
                $aggregated[$productCode]['total_quantity'] += $record['quantity'];
                $aggregated[$productCode]['total_sales'] += $record['subtotal'];
            }
        }

        // 商品コードでソート
        ksort($aggregated);

        return collect($aggregated)->values();
    }
}
