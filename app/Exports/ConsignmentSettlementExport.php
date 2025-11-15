<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsignmentSettlementExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    public function __construct(
        private Collection $products,
        private float $totalAmount,
    ) {}

    /**
     * Excel出力用のビューを返す
     */
    public function view(): \Illuminate\Contracts\View\View
    {
        return view('exports.consignment-settlement', [
            'products' => $this->products,
            'totalAmount' => $this->totalAmount,
        ]);
    }

    /**
     * シート名を設定
     */
    public function title(): string
    {
        return '精算書';
    }

    /**
     * 列幅を設定
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30, // 商品名
            'B' => 20, // 販売数量合計
        ];
    }

    /**
     * スタイルを設定
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]], // タイトル行
            2 => ['font' => ['bold' => true]], // ヘッダー行
        ];
    }
}
