<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\FromCollection;
use App\Exports\Concerns\WithCustomStartCell;
use App\Exports\Concerns\WithEvents;
use App\Exports\Concerns\WithHeadings;
use App\Exports\Concerns\WithTitle;
use App\Exports\Events\AfterSheet;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SettlementExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithEvents
{
    protected string $batchId;
    protected Collection $sales;
    protected array $headerInfo;

    public function __construct(string $batchId, Collection $sales, array $headerInfo)
    {
        $this->batchId = $batchId;
        $this->sales = $sales;
        $this->headerInfo = $headerInfo;
    }

    /**
     * コレクションを返す（データ行用）
     */
    public function collection(): Collection
    {
        return $this->sales->map(function ($sale) {
            // オブジェクトまたは配列の両方に対応
            $saleDate = is_object($sale) ? ($sale->sale_date ?? null) : ($sale['sale_date'] ?? null);
            $saleDateFormatted = '';
            if ($saleDate) {
                if ($saleDate instanceof \DateTime || $saleDate instanceof \Carbon\Carbon) {
                    $saleDateFormatted = $saleDate->format('Y/m/d');
                } elseif (is_string($saleDate)) {
                    try {
                        $date = new \DateTime($saleDate);
                        $saleDateFormatted = $date->format('Y/m/d');
                    } catch (\Exception $e) {
                        $saleDateFormatted = $saleDate;
                    }
                }
            }

            return [
                'sale_date' => $saleDateFormatted,
                'product_name' => is_object($sale) ? ($sale->product_name ?? '') : ($sale['product_name'] ?? ''),
                'unit_price' => is_object($sale) ? ($sale->unit_price ?? 0) : ($sale['unit_price'] ?? 0),
                'quantity' => is_object($sale) ? ($sale->quantity ?? 0) : ($sale['quantity'] ?? 0),
                'amount' => is_object($sale) ? ($sale->amount ?? 0) : ($sale['amount'] ?? 0),
                'commission' => is_object($sale) ? ($sale->commission ?? 0) : ($sale['commission'] ?? 0),
                'net_amount' => is_object($sale) ? ($sale->net_amount ?? 0) : ($sale['net_amount'] ?? 0),
                'notes' => is_object($sale) ? ($sale->notes ?? '') : ($sale['notes'] ?? ''),
            ];
        });
    }

    /**
     * ヘッダー行を返す
     */
    public function headings(): array
    {
        $commissionRate = $this->headerInfo['commission_rate'] ?? 0;
        return [
            '販売日',
            '商品名',
            '単価',
            '数量',
            '売上',
            '手数料(' . $commissionRate . '%)',
            '支払額',
            '備考',
        ];
    }

    /**
     * シートタイトルを返す
     */
    public function title(): string
    {
        return '委託販売精算書';
    }

    /**
     * データの開始セルを指定（ヘッダー行は5行目、データ行は6行目から）
     */
    public function startCell(): string
    {
        return 'A6';
    }

    /**
     * イベントを登録（レイアウト設定用）
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->getDelegate();

                // 1行目: 「委託販売精算書」（セル結合、太字、中央揃え）
                $sheet->setCellValue('A1', '委託販売精算書');
                $sheet->mergeCells('A1:H1');
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

                // 2行目: 請求期間
                $billingPeriod = $this->headerInfo['billing_period'] ?? '';
                $sheet->setCellValue('A2', '請求期間');
                $sheet->setCellValue('B2', $billingPeriod);

                // 3行目: 委託先名
                $vendorName = ($this->headerInfo['vendor_name'] ?? '') . ' 御中';
                $sheet->setCellValue('A3', '委託先名');
                $sheet->setCellValue('B3', $vendorName);

                // 4行目: 空行
                // （何もしない）

                // 5行目: ヘッダー行のスタイル設定（WithCustomStartCell('A6')により、ヘッダーは5行目に自動配置される）
                $headerRow = 5;

                // データ行の開始位置を計算（ヘッダー行の後）
                $dataStartRow = 6; // ヘッダー行は5行目
                $dataEndRow = $dataStartRow + $this->sales->count() - 1;

                // ヘッダー行のスタイル設定
                $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // データ行のスタイル設定
                if ($dataEndRow >= $dataStartRow) {
                    // 金額カラムの数値フォーマット設定
                    foreach (range($dataStartRow, $dataEndRow) as $row) {
                        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                        // データ行の枠線設定
                        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);
                    }
                }

                // 合計行の計算と設定
                $totalRow = $dataEndRow + 1;
                $totalQuantity = $this->sales->sum('quantity');
                $totalAmount = $this->sales->sum('amount');
                $totalCommission = $this->sales->sum('commission');
                $totalNetAmount = $this->sales->sum('net_amount');

                $sheet->setCellValue('A' . $totalRow, '合計');
                $sheet->setCellValue('B' . $totalRow, '');
                $sheet->setCellValue('C' . $totalRow, '');
                $sheet->setCellValue('D' . $totalRow, $totalQuantity);
                $sheet->setCellValue('E' . $totalRow, $totalAmount);
                $sheet->setCellValue('F' . $totalRow, $totalCommission);
                $sheet->setCellValue('G' . $totalRow, $totalNetAmount);
                $sheet->setCellValue('H' . $totalRow, '');

                // 合計行のスタイル設定
                $sheet->getStyle('A' . $totalRow . ':H' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // 合計行の金額フォーマット
                $sheet->getStyle('D' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle('E' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle('F' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle('G' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                // 振込先情報（合計の2行下）
                $bankInfoStartRow = $totalRow + 2;
                $sheet->setCellValue('A' . $bankInfoStartRow, '振込先');
                $sheet->getStyle('A' . $bankInfoStartRow)->getFont()->setBold(true);

                // 振込先情報の設定
                $sheet->setCellValue('A' . ($bankInfoStartRow + 1), '銀行名');
                $sheet->setCellValue('B' . ($bankInfoStartRow + 1), ''); // プレースホルダー
                $sheet->setCellValue('A' . ($bankInfoStartRow + 2), '支店名');
                $sheet->setCellValue('B' . ($bankInfoStartRow + 2), ''); // プレースホルダー
                $sheet->setCellValue('A' . ($bankInfoStartRow + 3), '口座種別');
                $sheet->setCellValue('B' . ($bankInfoStartRow + 3), ''); // プレースホルダー
                $sheet->setCellValue('A' . ($bankInfoStartRow + 4), '口座番号');
                $sheet->setCellValue('B' . ($bankInfoStartRow + 4), ''); // プレースホルダー
                $sheet->setCellValue('A' . ($bankInfoStartRow + 5), '口座名義');
                $sheet->setCellValue('B' . ($bankInfoStartRow + 5), ''); // プレースホルダー

                // 振込先情報の枠線設定
                $bankInfoEndRow = $bankInfoStartRow + 5;
                $sheet->getStyle('A' . $bankInfoStartRow . ':B' . $bankInfoEndRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // 列幅の自動調整
                foreach (range('A', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
