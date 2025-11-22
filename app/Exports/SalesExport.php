<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesExport
{
    /**
     * 全体集計済みの売上データ
     *
     * @var Collection<int, array{date: string, product_code: string, product_name: string, unit_price: int, total_quantity: int, total_sales: int}>
     */
    private Collection $data;

    /**
     * 各レジごとの集計データ
     *
     * @var array<int, array{name: string, aggregated: Collection<int, array{date: string, product_code: string, product_name: string, unit_price: int, total_quantity: int, total_sales: int}>}>
     */
    private array $registerData;

    /**
     * コンストラクタ
     *
     * @param  Collection<int, array{date: string, product_code: string, product_name: string, unit_price: int, total_quantity: int, total_sales: int}>  $aggregatedData  全体集計済みの売上データ
     * @param  array<int, array{name: string, aggregated: Collection}>  $registerAggregatedData  各レジごとの集計データ
     */
    public function __construct(Collection $aggregatedData, array $registerAggregatedData = [])
    {
        $this->data = $aggregatedData;
        $this->registerData = $registerAggregatedData;
    }

    /**
     * データのコレクションを返す（FromCollectionインターフェース風）
     *
     * @param  Collection<int, array{date: string, product_code: string, product_name: string, unit_price: int, total_quantity: int, total_sales: int}>  $data  集計データ
     * @return Collection<int, array{0: string, 1: string, 2: int, 3: int, 4: int}>
     */
    public function collection(Collection $data): Collection
    {
        return $data->map(function ($item) {
            return [
                0 => $item['product_code'],
                1 => $item['product_name'],
                2 => $item['unit_price'],
                3 => $item['total_quantity'],
                4 => $item['total_sales'],
            ];
        });
    }

    /**
     * ヘッダー行を返す（WithHeadingsインターフェース風）
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['商品コード', '商品名', '単価', '販売数', '売上金額'];
    }

    /**
     * シートにデータを書き込む
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet  シートオブジェクト
     * @param  Collection<int, array{date: string, product_code: string, product_name: string, unit_price: int, total_quantity: int, total_sales: int}>  $data  集計データ
     */
    private function writeSheetData($sheet, Collection $data): void
    {
        // ヘッダー行を設定
        $headers = $this->headings();
        $sheet->fromArray([$headers], null, 'A1');

        // ヘッダー行のスタイル設定
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // データ行を追加
        $row = 2;
        $totalQuantity = 0;
        $totalSales = 0;

        foreach ($this->collection($data) as $rowData) {
            $sheet->setCellValue('A' . $row, $rowData[0]); // product_code
            $sheet->setCellValue('B' . $row, $rowData[1]); // product_name
            $sheet->setCellValue('C' . $row, $rowData[2]); // unit_price
            $sheet->setCellValue('D' . $row, $rowData[3]); // total_quantity
            $sheet->setCellValue('E' . $row, $rowData[4]); // total_sales

            // 合計値を計算
            $totalQuantity += $rowData[3];
            $totalSales += $rowData[4];

            // 数値列の書式設定
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        // 合計行を追加
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, '合計');
        $sheet->setCellValue('B' . $totalRow, '');
        $sheet->setCellValue('C' . $totalRow, '');
        $sheet->setCellValue('D' . $totalRow, $totalQuantity);
        $sheet->setCellValue('E' . $totalRow, $totalSales);

        // 合計行のスタイル設定
        $totalStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D0D0D0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A' . $totalRow . ':E' . $totalRow)->applyFromArray($totalStyle);
        $sheet->getStyle('A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 合計行の数値列の書式設定
        $sheet->getStyle('D' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');

        // 列幅の自動調整
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Spreadsheetオブジェクトを取得（保存用）
     */
    public function getSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        // 最初のシート（全体集計）を設定
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('全体集計');
        $this->writeSheetData($sheet, $this->data);

        // 各レジごとのシートを作成
        foreach ($this->registerData as $register) {
            // 新しいシートを作成
            $sheet = $spreadsheet->createSheet();

            // シート名を設定（Excelのシート名は最大31文字、禁止文字を置換）
            $sheetName = mb_substr($register['name'], 0, 31);
            // Excelで使用できない文字を置換: [ ] : \ / ? * を削除または置換
            $sheetName = preg_replace('/[\[\]:\\\\\/\?\*]/', '', $sheetName);
            // 空文字列の場合はデフォルト名を使用
            if (empty($sheetName)) {
                $sheetName = 'レジ' . ($spreadsheet->getSheetCount());
            }
            $sheet->setTitle($sheetName);

            // データを書き込む
            $this->writeSheetData($sheet, $register['aggregated']);
        }

        // 最初のシート（全体集計）をアクティブにする
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * 集計データをExcelファイルとして出力する
     *
     * @return StreamedResponse Excelファイルのダウンロードレスポンス
     */
    public function export(): StreamedResponse
    {
        $spreadsheet = $this->getSpreadsheet();

        // ファイル名を生成（西暦日付を使用）
        $date = $this->data->first()['date'] ?? now()->format('Y/m/d');
        $dateFormatted = str_replace('/', '', $date);
        $filename = "盛岡手づくり村_日次売上集計_{$dateFormatted}.xlsx";

        // ストリームレスポンスを返す
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
