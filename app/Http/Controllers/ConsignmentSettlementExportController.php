<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ConsignmentSale;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsignmentSettlementExportController extends Controller
{
    /**
     * Excelファイルをダウンロード
     */
    public function download(Request $request): StreamedResponse
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

        // スプレッドシートを作成
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('精算書');

        // タイトル行
        $sheet->setCellValue('A1', '精算書');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // ヘッダー行
        $sheet->setCellValue('A2', '商品名');
        $sheet->setCellValue('B2', '販売数量合計');
        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);

        // データ行
        $row = 3;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product['product_name']);
            $sheet->setCellValue('B' . $row, $product['total_quantity']);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        // 合計行
        $sheet->setCellValue('A' . $row, '合計金額');
        $sheet->setCellValue('B' . $row, '¥' . number_format($totalAmount));
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F9FAFB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 列幅を設定
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);

        // ファイル名
        $fileName = '精算書_' . now()->format('YmdHis') . '.xlsx';

        // レスポンスを返す
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
