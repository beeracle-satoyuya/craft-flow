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
        Schema::create('sales_aggregations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('aggregated_at');
            $table->string('excel_filename');
            $table->string('excel_file_path');
            $table->json('original_pdf_files'); // 元のPDFファイル名の配列
            $table->bigInteger('total_sales_amount')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->timestamps();

            // インデックス追加（パフォーマンス向上）
            $table->index('aggregated_at');
            $table->index('user_id');
            $table->index(['aggregated_at', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_aggregations');
    }
};
