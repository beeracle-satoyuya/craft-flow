<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * データベースシーダー
 */
class DatabaseSeeder extends Seeder
{
    /**
     * アプリケーションのデータベースをシード
     */
    public function run(): void
    {
        // テストユーザーを作成
        User::factory()->create([
            'name' => '染物屋 太郎',
            'email' => 'staff@somemonoya-takahashi.jp',
            'password' => Hash::make('password'),
        ]);

        // 追加のテストユーザー
        User::factory()->create([
            'name' => '高橋 花子',
            'email' => 'takahashi@somemonoya-takahashi.jp',
            'password' => Hash::make('password'),
        ]);

        // 体験プログラムカテゴリと体験プログラムをシード
        $this->call([
            WorkshopCategorySeeder::class,
            WorkshopSeeder::class,
        ]);

        $this->command->info('初期データの投入が完了しました！');
        $this->command->info('ログイン情報:');
        $this->command->info('  Email: staff@somemonoya-takahashi.jp');
        $this->command->info('  Password: password');
    }
}

