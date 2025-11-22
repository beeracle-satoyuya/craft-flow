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
        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('sale_date'); // レシート番号
            $table->string('client_id')->nullable()->after('receipt_number'); // クライアントID
            $table->string('company_name')->nullable()->after('client_id'); // 会社名
            $table->string('product_code')->nullable()->after('company_name'); // 商品コード
            $table->string('category')->nullable()->after('amount'); // カテゴリ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->dropColumn(['receipt_number', 'client_id', 'company_name', 'product_code', 'category']);
        });
    }
};
