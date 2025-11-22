<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

/**
 * maatwebsite/excelのWithTitleインターフェースの代替
 */
interface WithTitle
{
    /**
     * シートタイトルを返す
     */
    public function title(): string;
}

