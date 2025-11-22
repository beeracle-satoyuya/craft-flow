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
        Schema::create('daily_sales_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date')->index();
            $table->string('product_code')->index();
            $table->string('product_name');
            $table->bigInteger('total_sales_amount')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->string('register_name')->nullable()->index();
            $table->timestamps();

            // 複合インデックス（重複防止と高速検索）
            $table->unique(['sale_date', 'product_code', 'register_name'], 'daily_summary_unique');
            $table->index(['sale_date', 'product_code'], 'daily_date_product_idx');
            $table->index(['sale_date', 'register_name'], 'daily_date_register_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_summaries');
    }
};
