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
        Schema::create('monthly_sales_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('year_month', 7)->index(); // YYYY/MM形式
            $table->string('product_code')->index();
            $table->string('product_name');
            $table->bigInteger('total_sales_amount')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->string('register_name')->nullable()->index();
            $table->timestamps();

            // 複合インデックス
            $table->unique(['year_month', 'product_code', 'register_name'], 'monthly_summary_unique');
            $table->index(['year_month', 'product_code'], 'monthly_year_product_idx');
            $table->index(['year_month', 'register_name'], 'monthly_year_register_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_sales_summaries');
    }
};
