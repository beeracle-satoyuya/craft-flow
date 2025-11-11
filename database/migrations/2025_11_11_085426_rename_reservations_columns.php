<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * マイグレーションの実行
     * reservationsテーブルの主キーと外部キー名を変更
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLiteの場合: テーブルを再作成
            DB::statement('PRAGMA foreign_keys = OFF');
            
            // 一時テーブルを作成
            Schema::create('reservations_new', function (Blueprint $table) {
                $table->id('reservation_id'); // 主キーをreservation_idに
                $table->unsignedBigInteger('program_id'); // workshop_idからprogram_idに
                $table->unsignedBigInteger('staff_id'); // user_idからstaff_idに
                $table->string('customer_name');
                $table->string('customer_email');
                $table->string('customer_phone');
                $table->dateTime('reservation_datetime');
                $table->integer('num_people');
                $table->string('status')->default('pending');
                $table->string('source');
                $table->text('comment')->nullable();
                $table->json('options')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->softDeletes();
                $table->timestamps();
                
                // 外部キー制約
                $table->foreign('program_id')->references('program_id')->on('workshops')->onDelete('cascade');
                $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
            });
            
            // データをコピー
            DB::statement('INSERT INTO reservations_new (reservation_id, program_id, staff_id, customer_name, customer_email, customer_phone, reservation_datetime, num_people, status, source, comment, options, cancellation_reason, deleted_at, created_at, updated_at) SELECT id, workshop_id, user_id, customer_name, customer_email, customer_phone, reservation_datetime, num_people, status, source, comment, options, cancellation_reason, deleted_at, created_at, updated_at FROM reservations');
            
            // 元のテーブルを削除
            Schema::dropIfExists('reservations');
            
            // 新しいテーブルをリネーム
            Schema::rename('reservations_new', 'reservations');
            
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL/PostgreSQLの場合
            Schema::table('reservations', function (Blueprint $table) {
                // 外部キー制約を削除
                $table->dropForeign(['workshop_id']);
                $table->dropForeign(['user_id']);
            });
            
            // カラム名変更
            Schema::table('reservations', function (Blueprint $table) {
                $table->renameColumn('id', 'reservation_id');
                $table->renameColumn('workshop_id', 'program_id');
                $table->renameColumn('user_id', 'staff_id');
            });
            
            // 外部キー制約を再作成
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('program_id')->references('program_id')->on('workshops')->onDelete('cascade');
                $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
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
            
            Schema::create('reservations_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workshop_id')->constrained('workshops')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('customer_name');
                $table->string('customer_email');
                $table->string('customer_phone');
                $table->dateTime('reservation_datetime');
                $table->integer('num_people');
                $table->string('status')->default('pending');
                $table->string('source');
                $table->text('comment')->nullable();
                $table->json('options')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
            
            DB::statement('INSERT INTO reservations_old (id, workshop_id, user_id, customer_name, customer_email, customer_phone, reservation_datetime, num_people, status, source, comment, options, cancellation_reason, deleted_at, created_at, updated_at) SELECT reservation_id, program_id, staff_id, customer_name, customer_email, customer_phone, reservation_datetime, num_people, status, source, comment, options, cancellation_reason, deleted_at, created_at, updated_at FROM reservations');
            
            Schema::dropIfExists('reservations');
            Schema::rename('reservations_old', 'reservations');
            
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['program_id']);
                $table->dropForeign(['staff_id']);
            });
            
            Schema::table('reservations', function (Blueprint $table) {
                $table->renameColumn('reservation_id', 'id');
                $table->renameColumn('program_id', 'workshop_id');
                $table->renameColumn('staff_id', 'user_id');
            });
            
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('workshop_id')->references('id')->on('workshops')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};
