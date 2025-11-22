<?php

declare(strict_types=1);

namespace App\Exports\Events;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * maatwebsite/excelのAfterSheetイベントの代替
 */
class AfterSheet
{
    protected Worksheet $sheet;

    public function __construct(Worksheet $sheet)
    {
        $this->sheet = $sheet;
    }

    /**
     * シートを取得
     */
    public function getDelegate(): Worksheet
    {
        return $this->sheet;
    }
}

