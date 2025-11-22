<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use App\Models\SalesAggregation;
use App\Models\SalesAggregationItem;
use App\Observers\SalesAggregationItemObserver;
use App\Services\PdfSalesExtractorService;
use App\Services\SalesAggregationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Smalot\PdfParser\Parser;

class SalesController extends Controller
{
    public function __construct(
        private PdfSalesExtractorService $extractorService,
        private SalesAggregationService $aggregationService
    ) {}

    /**
     * アップロードされたPDFファイルを処理し、集計結果をExcelファイルとして出力する
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function aggregateAndExport(Request $request)
    {
        // セッションからファイルパスを取得（Voltコンポーネントから渡された場合）
        $filePaths = session()->get('pdf_files_paths');

        try {
            $allSalesData = [];
            $filesToProcess = [];
            $fileNames = []; // ファイル名を保持

            if ($filePaths && is_array($filePaths) && ! empty($filePaths)) {
                // Voltコンポーネントから渡されたファイルパスを使用
                foreach ($filePaths as $filePath) {
                    if (file_exists($filePath)) {
                        $filesToProcess[] = $filePath;
                        $fileNames[] = basename($filePath, '.pdf'); // 拡張子を除いたファイル名
                    }
                }
                session()->forget('pdf_files_paths');
            } else {
                // 通常のフォーム送信からファイルを取得
                $request->validate([
                    'pdf_files' => 'required|array|min:1',
                    'pdf_files.*' => 'required|file|mimes:pdf|max:10240', // 10MBまで
                ]);

                foreach ($request->file('pdf_files') as $pdfFile) {
                    $filesToProcess[] = $pdfFile->getRealPath();
                    $fileNames[] = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME); // 拡張子を除いたファイル名
                }
            }

            if (empty($filesToProcess)) {
                return back()->withErrors(['pdf_files' => 'PDFファイルが見つかりませんでした。']);
            }

            // 各PDFファイルを処理
            $registerData = []; // 各レジのデータを保持
            foreach ($filesToProcess as $index => $filePath) {
                $fullPath = $filePath;
                $fileName = $fileNames[$index] ?? 'レジ' . ($index + 1);

                try {
                    // PDFからテキストを抽出
                    $parser = new Parser;
                    $pdf = $parser->parseFile($fullPath);
                    $pdfText = $pdf->getText();

                    // 売上データを抽出
                    $salesData = $this->extractorService->extractSalesData($pdfText);
                    $allSalesData[] = $salesData;

                    // 各レジのデータを個別に保持
                    // レジ番号が取得できた場合はそれを使用、なければファイル名を使用
                    $registerName = ! empty($salesData['register_number']) ? $salesData['register_number'] : $fileName;
                    $registerData[] = [
                        'name' => $registerName,
                        'data' => $salesData,
                    ];
                } catch (\Exception $e) {
                    Log::error('PDF解析エラー: ' . $e->getMessage(), [
                        'file' => basename($filePath),
                    ]);
                    // エラーが発生しても次のファイルの処理を続行
                }
            }

            if (empty($allSalesData)) {
                return back()->withErrors(['pdf_files' => '有効な売上データが見つかりませんでした。']);
            }

            // 各レジごとの集計データを生成
            $registerAggregatedData = [];
            foreach ($registerData as $register) {
                $registerAggregatedData[] = [
                    'name' => $register['name'],
                    'aggregated' => $this->aggregationService->aggregate([$register['data']]),
                ];
            }

            // 全レジのデータを集計
            $aggregatedData = $this->aggregationService->aggregate($allSalesData);

            // Excelファイルとして出力
            $export = new SalesExport($aggregatedData, $registerAggregatedData);

            // Excelファイルをストレージに保存
            $spreadsheet = $export->getSpreadsheet();
            $date = $aggregatedData->first()['date'] ?? now()->format('Y/m/d');
            $dateFormatted = str_replace('/', '', $date);
            $filename = "盛岡手づくり村_日次売上集計_{$dateFormatted}.xlsx";

            // ストレージに保存
            $directory = 'private/sales';
            Storage::disk('local')->makeDirectory($directory);
            $filePath = $directory . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $fullPath = Storage::disk('local')->path($filePath);
            $writer->save($fullPath);

            // 集計履歴を保存
            $totalSalesAmount = $aggregatedData->sum('total_sales');
            $totalQuantity = $aggregatedData->sum('total_quantity');

            $salesAggregation = SalesAggregation::create([
                'user_id' => Auth::id(),
                'aggregated_at' => now(),
                'excel_filename' => $filename,
                'excel_file_path' => $filePath,
                'original_pdf_files' => $fileNames,
                'total_sales_amount' => $totalSalesAmount,
                'total_quantity' => $totalQuantity,
            ]);

            // Observerを一時無効化してバッチ処理を最適化
            SalesAggregationItemObserver::disable();

            try {
                // 影響を受ける日付を収集
                $affectedDates = [];

                // 集計詳細アイテムを保存（全体集計）
                $itemsToInsert = [];
                foreach ($aggregatedData as $item) {
                    $date = $item['date'];
                    if (! in_array($date, $affectedDates)) {
                        $affectedDates[] = $date;
                    }

                    $itemsToInsert[] = [
                        'sales_aggregation_id' => $salesAggregation->id,
                        'product_code' => $item['product_code'],
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['total_quantity'],
                        'sales_amount' => $item['total_sales'],
                        'register_name' => null, // 全体集計なのでNULL
                        'sale_date' => $date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // 各レジごとの詳細も保存
                foreach ($registerAggregatedData as $register) {
                    foreach ($register['aggregated'] as $item) {
                        $date = $item['date'];
                        if (! in_array($date, $affectedDates)) {
                            $affectedDates[] = $date;
                        }

                        $itemsToInsert[] = [
                            'sales_aggregation_id' => $salesAggregation->id,
                            'product_code' => $item['product_code'],
                            'product_name' => $item['product_name'],
                            'unit_price' => $item['unit_price'],
                            'quantity' => $item['total_quantity'],
                            'sales_amount' => $item['total_sales'],
                            'register_name' => $register['name'],
                            'sale_date' => $date,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // 一括挿入（チャンク処理でメモリ効率化）
                foreach (array_chunk($itemsToInsert, 500) as $chunk) {
                    SalesAggregationItem::insert($chunk);
                }

                // バッチ処理後の一括再集計を実行
                SalesAggregationItemObserver::batchUpdateSummaries($affectedDates);
            } finally {
                // Observerを再有効化
                SalesAggregationItemObserver::enable();
            }

            // ダウンロードレスポンスを返す
            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        } catch (\Exception $e) {
            Log::error('売上集計エラー: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['pdf_files' => '処理中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }
}
