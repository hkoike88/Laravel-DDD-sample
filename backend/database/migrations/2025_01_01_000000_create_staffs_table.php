<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * staffs テーブルのマイグレーション
 *
 * 職員情報を格納するテーブルを作成する。
 */
return new class extends Migration
{
    /**
     * マイグレーション実行
     */
    public function up(): void
    {
        Schema::create('staffs', function (Blueprint $table) {
            $table->char('id', 26)->primary()->comment('職員ID（ULID）');
            $table->string('email', 255)->unique()->comment('メールアドレス（小文字正規化済み）');
            $table->string('password', 255)->comment('ハッシュ化済みパスワード');
            $table->string('name', 100)->comment('職員名');
            $table->boolean('is_locked')->default(false)->comment('ロック状態');
            $table->unsignedInteger('failed_login_attempts')->default(0)->comment('ログイン失敗回数');
            $table->timestamp('locked_at')->nullable()->comment('ロック日時');
            $table->timestamps();
        });
    }

    /**
     * マイグレーションロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
