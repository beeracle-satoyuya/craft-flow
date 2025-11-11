<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WorkshopCategory;
use Illuminate\Database\Seeder;

/**
 * 体験プログラムカテゴリの初期データを投入
 */
class WorkshopCategorySeeder extends Seeder
{
    /**
     * シーダーを実行
     */
    public function run(): void
    {
        $categories = [
            ['name' => '藍染め体験'],
            ['name' => '型染め体験'],
            ['name' => '絞り染め体験'],
            ['name' => '草木染め体験'],
            ['name' => '手ぬぐい染め体験'],
        ];

        foreach ($categories as $category) {
            WorkshopCategory::firstOrCreate($category);
        }
    }
}
