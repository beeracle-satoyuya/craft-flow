<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\FromView;
use App\Exports\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * maatwebsite/excelのExcelファサードの代替
 */
class Excel
{
    /**
     * Excelファイルをダウンロード
     *
     * @param object $export Exportクラスのインスタンス
     * @param string $fileName ファイル名
     * @return StreamedResponse
     */
    public static function download(object $export, string $fileName): StreamedResponse
    {
        // FromViewインターフェースの場合は、HTMLからExcelを生成
        if ($export instanceof FromView) {
            return self::downloadFromView($export, $fileName);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // シートタイトルを設定
        if (method_exists($export, 'title')) {
            $sheet->setTitle($export->title());
        }

        // データの開始セルを取得
        $startCell = 'A1';
        if (method_exists($export, 'startCell')) {
            $startCell = $export->startCell();
        }

        // 開始セルから行番号と列番号を取得
        preg_match('/([A-Z]+)(\d+)/', $startCell, $matches);
        $startColumn = $matches[1] ?? 'A';
        $startRow = (int) ($matches[2] ?? 1);
        $headerRow = $startRow - 1; // ヘッダー行はデータ行の1行前

        // ヘッダー行を設定
        if (method_exists($export, 'headings')) {
            $headings = $export->headings();
            $columnIndex = Coordinate::columnIndexFromString($startColumn);
            foreach ($headings as $heading) {
                $column = Coordinate::stringFromColumnIndex($columnIndex);
                $sheet->setCellValue($column . $headerRow, $heading);
                $columnIndex++;
            }
        }

        // データ行を設定
        if (method_exists($export, 'collection')) {
            $collection = $export->collection();
            $currentRow = $startRow;
            foreach ($collection as $row) {
                $columnIndex = Coordinate::columnIndexFromString($startColumn);
                foreach ($row as $value) {
                    $column = Coordinate::stringFromColumnIndex($columnIndex);
                    $sheet->setCellValue($column . $currentRow, $value);
                    $columnIndex++;
                }
                $currentRow++;
            }
        }

        // イベントを実行
        if (method_exists($export, 'registerEvents')) {
            $events = $export->registerEvents();
            foreach ($events as $eventClass => $callback) {
                if ($eventClass === \App\Exports\Events\AfterSheet::class) {
                    $event = new \App\Exports\Events\AfterSheet($sheet);
                    $callback($event);
                }
            }
        }

        // レスポンスを返す
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * FromViewからExcelファイルをダウンロード
     *
     * @param FromView $export Exportクラスのインスタンス
     * @param string $fileName ファイル名
     * @return StreamedResponse
     */
    protected static function downloadFromView(FromView $export, string $fileName): StreamedResponse
    {
        // ビューをレンダリング
        $viewName = $export->view();
        $viewData = method_exists($export, 'data') ? $export->data() : [];
        $html = View::make($viewName, $viewData)->render();

        // HTMLからSpreadsheetを生成
        $reader = IOFactory::createReader('Html');
        $spreadsheet = $reader->loadFromString($html);

        // シートタイトルを設定
        if (method_exists($export, 'title')) {
            $spreadsheet->getActiveSheet()->setTitle($export->title());
        }

        // ShouldAutoSizeインターフェースが実装されている場合は列幅を自動調整
        if ($export instanceof ShouldAutoSize) {
            $sheet = $spreadsheet->getActiveSheet();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
        }

        // レスポンスを返す
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

