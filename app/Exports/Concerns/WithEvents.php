<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

/**
 * maatwebsite/excelのWithEventsインターフェースの代替
 */
interface WithEvents
{
    /**
     * イベントを登録
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array;
}

