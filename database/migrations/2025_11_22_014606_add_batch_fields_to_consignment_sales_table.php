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
            $table->string('batch_id')->nullable()->after('id')->index(); // バッチID
            $table->string('vendor_name')->nullable()->after('batch_id'); // 委託先名
            $table->integer('commission_rate')->nullable()->after('vendor_name'); // 手数料率（%）
            $table->integer('net_amount')->nullable()->after('commission'); // 実際に支払う金額
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'vendor_name', 'commission_rate', 'net_amount']);
        });
    }
};
