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
            $table->id('reservation_id'); // 最初からreservation_idとして作成
            $table->unsignedBigInteger('program_id'); // workshop_idの代わりにprogram_idを使用
            $table->unsignedBigInteger('staff_id'); // user_idの代わりにstaff_idを使用
            $table->string('customer_name'); // 顧客氏名
            $table->string('customer_email'); // 顧客メールアドレス
            $table->string('customer_phone'); // 顧客電話番号
            $table->dateTime('reservation_datetime'); // 予約日時
            $table->integer('num_people'); // 人数
            $table->string('status')->default('pending'); // 予約状況（pending, confirmed, canceled）
            $table->string('source'); // 予約経路（web, phone, walk-in, asoview, jalan）
            $table->text('comment')->nullable(); // コメント
            $table->json('options')->nullable(); // オプション
            $table->text('cancellation_reason')->nullable(); // キャンセル理由
            $table->softDeletes(); // 削除フラグ
            $table->timestamps();
            
            // 外部キー制約
            $table->foreign('program_id')->references('program_id')->on('workshops')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
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
