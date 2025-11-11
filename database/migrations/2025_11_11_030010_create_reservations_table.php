<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションの実行
     * 予約情報テーブルを作成
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->onDelete('cascade'); // 体験プログラムへの外部キー
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 予約を登録したスタッフ
            $table->string('customer_name'); // 顧客氏名
            $table->string('customer_email'); // 顧客メールアドレス
            $table->string('customer_phone'); // 顧客電話番号
            $table->dateTime('reservation_datetime'); // 予約日時
            $table->integer('num_people'); // 人数
            $table->string('status')->default('pending'); // 予約状況（pending, confirmed, canceled）
            $table->string('source'); // 予約経路（web, phone, walk-in）
            $table->text('comment')->nullable(); // コメント
            $table->json('options')->nullable(); // オプション
            $table->text('cancellation_reason')->nullable(); // キャンセル理由
            $table->softDeletes(); // 削除フラグ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
