<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\Excel;
use App\Livewire\ConsignmentSales\SettlementExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ConsignmentSettlementExportController extends Controller
{
    /**
     * Excelファイルをダウンロード
     */
    public function download(Request $request)
    {
        $batchId = $request->query('batch_id');
        $vendorName = $request->query('vendor_name');

        // batchIdまたはvendorNameのいずれかが必要
        if (! $batchId && ! $vendorName) {
            abort(404, 'バッチIDまたは委託先名が指定されていません');
        }

        // ファイル名を生成
        $safeVendorName = preg_replace('/[\/\\\?%*:|"<>]/', '_', $vendorName ?? '一括出力');
        $date = now()->format('Ymd');
        $fileName = "委託販売精算書_{$safeVendorName}_{$date}.xlsx";

        // SettlementExportクラスを使用（batchIdとvendorNameを渡す）
        $export = new SettlementExport($batchId ?? '', $vendorName);

        // Excelファイルをダウンロード
        $response = Excel::download($export, $fileName);

        // Excel出力後にバッチデータをクリア
        $allSessions = Session::all();
        $deletedCount = 0;

        foreach ($allSessions as $key => $value) {
            if (str_starts_with($key, 'consignment_sales_batch_')) {
                Session::forget($key);
                $deletedCount++;
            }
        }

        return $response;
    }
}
