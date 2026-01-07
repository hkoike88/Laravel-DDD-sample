<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * staffs テーブルに is_admin カラムを追加
 *
 * 管理者フラグを追加し、同時ログイン数の制限を職員種別で分ける。
 * - 一般職員（is_admin = false）: 最大3台
 * - 管理者（is_admin = true）: 最大1台
 */
return new class extends Migration
{
    /**
     * マイグレーション実行
     */
    public function up(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->boolean('is_admin')
                ->default(false)
                ->after('name')
                ->comment('管理者フラグ');
        });
    }

    /**
     * マイグレーションロールバック
     */
    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
