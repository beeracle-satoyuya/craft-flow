<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

/**
 * maatwebsite/excelのFromViewインターフェースの代替
 */
interface FromView
{
    /**
     * ビュー名を返す
     *
     * @return string
     */
    public function view(): string;
}

