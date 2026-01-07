<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * パスワード履歴テーブルのマイグレーション
 *
 * 職員のパスワード履歴を管理し、過去5世代のパスワード再利用を禁止するために使用。
 *
 * @feature 001-security-preparation
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            // パスワード履歴ID（ULID）
            $table->char('id', 26)->primary()->comment('パスワード履歴ID（ULID）');

            // 職員ID（外部キー）
            $table->char('staff_id', 26)->comment('職員ID');

            // ハッシュ化済みパスワード
            $table->string('password_hash', 255)->comment('ハッシュ化済みパスワード');

            // 作成日時
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');

            // インデックス: 職員ごとの履歴取得（最新順）
            $table->index(['staff_id', 'created_at'], 'idx_staff_id_created_at');

            // 外部キー制約
            $table->foreign('staff_id')
                ->references('id')
                ->on('staffs')
                ->onDelete('cascade');
        });

        // テーブルコメント
        Schema::getConnection()->statement("ALTER TABLE password_histories COMMENT 'パスワード履歴'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
