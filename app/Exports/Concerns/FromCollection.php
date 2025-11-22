<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

use Illuminate\Support\Collection;

/**
 * maatwebsite/excelのFromCollectionインターフェースの代替
 */
interface FromCollection
{
    /**
     * コレクションを返す
     */
    public function collection(): Collection;
}

