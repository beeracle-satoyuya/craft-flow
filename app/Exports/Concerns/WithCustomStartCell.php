<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

/**
 * maatwebsite/excelのWithCustomStartCellインターフェースの代替
 */
interface WithCustomStartCell
{
    /**
     * データの開始セルを指定
     */
    public function startCell(): string;
}

