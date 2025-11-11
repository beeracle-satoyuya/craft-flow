<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * マイグレーションの実行
     * workshopsテーブルの主キーとカラム名を変更
     */
    public function up(): void
    {
        // SQLiteの場合は主キーの変更が制限されるため、DBファサードを使用
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLiteの場合: テーブルを再作成
            Schema::table('workshops', function (Blueprint $table) {
                // 外部キー制約を一時的に無効化
                DB::statement('PRAGMA foreign_keys = OFF');
            });
            
            // 一時テーブルを作成
            Schema::create('workshops_new', function (Blueprint $table) {
                $table->id('program_id'); // 主キーをprogram_idに
                $table->foreignId('workshop_category_id')->constrained('workshop_categories')->onDelete('cascade');
                $table->string('program_name'); // nameからprogram_nameに
                $table->text('description')->nullable();
                $table->integer('duration_minutes');
                $table->integer('max_capacity');
                $table->integer('price_per_person');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            
            // データをコピー
            DB::statement('INSERT INTO workshops_new (program_id, workshop_category_id, program_name, description, duration_minutes, max_capacity, price_per_person, is_active, created_at, updated_at) SELECT id, workshop_category_id, name, description, duration_minutes, max_capacity, price_per_person, is_active, created_at, updated_at FROM workshops');
            
            // 元のテーブルを削除
            Schema::dropIfExists('workshops');
            
            // 新しいテーブルをリネーム
            Schema::rename('workshops_new', 'workshops');
            
            // 外部キー制約を再度有効化
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL/PostgreSQLの場合
            Schema::table('workshops', function (Blueprint $table) {
                // 外部キー制約を削除
                $table->dropForeign(['workshop_category_id']);
            });
            
            // カラム名変更
            Schema::table('workshops', function (Blueprint $table) {
                $table->renameColumn('name', 'program_name');
                $table->renameColumn('id', 'program_id');
            });
            
            // 外部キー制約を再作成
            Schema::table('workshops', function (Blueprint $table) {
                $table->foreign('workshop_category_id')->references('id')->on('workshop_categories')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            
            Schema::create('workshops_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workshop_category_id')->constrained('workshop_categories')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('duration_minutes');
                $table->integer('max_capacity');
                $table->integer('price_per_person');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            
            DB::statement('INSERT INTO workshops_old (id, workshop_category_id, name, description, duration_minutes, max_capacity, price_per_person, is_active, created_at, updated_at) SELECT program_id, workshop_category_id, program_name, description, duration_minutes, max_capacity, price_per_person, is_active, created_at, updated_at FROM workshops');
            
            Schema::dropIfExists('workshops');
            Schema::rename('workshops_old', 'workshops');
            
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('workshops', function (Blueprint $table) {
                $table->dropForeign(['workshop_category_id']);
            });
            
            Schema::table('workshops', function (Blueprint $table) {
                $table->renameColumn('program_name', 'name');
                $table->renameColumn('program_id', 'id');
            });
            
            Schema::table('workshops', function (Blueprint $table) {
                $table->foreign('workshop_category_id')->references('id')->on('workshop_categories')->onDelete('cascade');
            });
        }
    }
};
