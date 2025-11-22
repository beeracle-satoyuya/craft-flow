<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use App\Services\PdfSalesExtractorService;
use App\Services\SalesAggregationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

            return $export->export();
        } catch (\Exception $e) {
            Log::error('売上集計エラー: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['pdf_files' => '処理中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }
}
