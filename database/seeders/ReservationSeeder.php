<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;

/**
 * 予約データの初期データを投入
 */
class ReservationSeeder extends Seeder
{
    /**
     * シーダーを実行
     */
    public function run(): void
    {
        // 100件の予約データを作成
        Reservation::factory()
            ->count(100)
            ->create();
    }
}




