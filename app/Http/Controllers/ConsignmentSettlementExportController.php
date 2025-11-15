<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ConsignmentSettlementExport;
use App\Models\ConsignmentSale;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ConsignmentSettlementExportController extends Controller
{
    /**
     * Excelファイルをダウンロード
     */
    public function download(Request $request)
    {
        // データベースから委託販売データを取得して集計
        $sales = ConsignmentSale::query()
            ->orderBy('created_at', 'desc')
            ->get();

        // 商品名ごとに集計
        $products = $sales->groupBy('product_name')->map(function (Collection $items) {
            return [
                'product_name' => $items->first()->product_name,
                'total_quantity' => $items->sum('quantity'),
                'total_amount' => $items->sum('amount'),
            ];
        })->values();

        // 合計金額を計算
        $totalAmount = ConsignmentSale::query()->sum('amount');

        $fileName = '精算書_'.now()->format('YmdHis').'.xlsx';

        return Excel::download(
            new ConsignmentSettlementExport($products, $totalAmount),
            $fileName
        );
    }
}
