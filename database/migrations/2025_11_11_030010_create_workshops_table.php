<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションの実行
     * 体験プログラムテーブルを作成
     */
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_category_id')->constrained('workshop_categories')->onDelete('cascade'); // カテゴリへの外部キー
            $table->string('name'); // 体験プログラム名
            $table->text('description')->nullable(); // 説明
            $table->integer('duration_minutes'); // 所用時間（分単位）
            $table->integer('max_capacity'); // 最大受入人数
            $table->integer('price_per_person'); // 料金（1人）
            $table->boolean('is_active')->default(true); // アクティブフラグ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
