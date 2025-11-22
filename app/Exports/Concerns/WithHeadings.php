<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

/**
 * maatwebsite/excelのWithHeadingsインターフェースの代替
 */
interface WithHeadings
{
    /**
     * ヘッダー行を返す
     *
     * @return array<string>
     */
    public function headings(): array;
}

