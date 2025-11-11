<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションの実行
     * 体験プログラムのカテゴリテーブルを作成
     */
    public function up(): void
    {
        Schema::create('workshop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 体験プログラム コース種類
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_categories');
    }
};
