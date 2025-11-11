<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Workshop;
use App\Models\WorkshopCategory;
use Illuminate\Database\Seeder;

/**
 * 体験プログラムの初期データを投入
 */
class WorkshopSeeder extends Seeder
{
    /**
     * シーダーを実行
     */
    public function run(): void
    {
        // カテゴリを取得
        $categories = WorkshopCategory::all()->keyBy('name');

        $workshops = [
            // 藍染め体験
            [
                'workshop_category_id' => $categories['藍染め体験']->id,
                'program_name' => '藍染めハンカチ体験',
                'description' => '伝統的な藍染めでオリジナルのハンカチを作ります。初心者の方でも楽しめます。',
                'duration_minutes' => 90,
                'max_capacity' => 8,
                'price_per_person' => 3000,
                'is_active' => true,
            ],
            [
                'workshop_category_id' => $categories['藍染め体験']->id,
                'program_name' => '藍染めストール体験',
                'description' => '大判のストールを藍染めします。自分だけの模様を作れます。',
                'duration_minutes' => 120,
                'max_capacity' => 6,
                'price_per_person' => 5500,
                'is_active' => true,
            ],
            // 型染め体験
            [
                'workshop_category_id' => $categories['型染め体験']->id,
                'program_name' => '型染めトートバッグ体験',
                'description' => '伝統的な型紙を使ってトートバッグに模様を染めます。',
                'duration_minutes' => 120,
                'max_capacity' => 6,
                'price_per_person' => 4500,
                'is_active' => true,
            ],
            // 絞り染め体験
            [
                'workshop_category_id' => $categories['絞り染め体験']->id,
                'program_name' => '絞り染めTシャツ体験',
                'description' => '糸で縛って染める伝統技法でオリジナルTシャツを作ります。',
                'duration_minutes' => 150,
                'max_capacity' => 8,
                'price_per_person' => 4000,
                'is_active' => true,
            ],
            // 草木染め体験
            [
                'workshop_category_id' => $categories['草木染め体験']->id,
                'program_name' => '草木染めストール体験',
                'description' => '自然の植物を使った優しい色合いのストールを染めます。',
                'duration_minutes' => 180,
                'max_capacity' => 5,
                'price_per_person' => 6000,
                'is_active' => true,
            ],
            // 手ぬぐい染め体験
            [
                'workshop_category_id' => $categories['手ぬぐい染め体験']->id,
                'program_name' => '手ぬぐい染め体験（藍）',
                'description' => '伝統の藍染めで手ぬぐいを染めます。日常使いに最適です。',
                'duration_minutes' => 90,
                'max_capacity' => 10,
                'price_per_person' => 2500,
                'is_active' => true,
            ],
        ];

        foreach ($workshops as $workshop) {
            Workshop::firstOrCreate(
                ['program_name' => $workshop['program_name']],
                $workshop
            );
        }
    }
}
