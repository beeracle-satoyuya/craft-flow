<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 委託販売テーブルを作成
     */
    public function up(): void
    {
        Schema::create('consignment_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date'); // 販売日
            $table->string('product_name'); // 商品名
            $table->integer('quantity')->default(1); // 数量
            $table->integer('unit_price'); // 単価
            $table->integer('amount'); // 金額
            $table->integer('commission')->default(0); // 手数料
            $table->text('notes')->nullable(); // 備考
            $table->softDeletes(); // 削除フラグ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_sales');
    }
};
