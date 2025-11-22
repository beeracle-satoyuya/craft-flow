<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_aggregation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_aggregation_id')->constrained()->onDelete('cascade');
            $table->string('product_code');
            $table->string('product_name');
            $table->integer('unit_price');
            $table->integer('quantity');
            $table->bigInteger('sales_amount');
            $table->string('register_name')->nullable(); // レジ名
            $table->date('sale_date'); // 販売日
            $table->timestamps();

            // インデックス追加（パフォーマンス向上）
            $table->index('sales_aggregation_id');
            $table->index('product_code');
            $table->index('sale_date');
            $table->index('register_name');
            $table->index(['sale_date', 'product_code']);
            $table->index(['sale_date', 'register_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_aggregation_items');
    }
};
