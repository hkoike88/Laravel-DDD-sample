<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * books テーブルに登録者情報カラムを追加するマイグレーション
 *
 * 蔵書登録時に登録者（職員）と登録日時を記録するためのカラムを追加。
 * EPIC-002: 蔵書登録機能の要件 FR-010 に対応。
 */
return new class extends Migration
{
    /**
     * マイグレーション実行
     *
     * registered_by: 登録を実行した職員のID（ULID 26文字）
     * registered_at: 登録日時（タイムスタンプ）
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // 登録者ID（職員のULID）
            $table->char('registered_by', 26)
                ->nullable()
                ->after('status')
                ->comment('登録者ID（職員ULID）');

            // 登録日時
            $table->timestamp('registered_at')
                ->nullable()
                ->after('registered_by')
                ->comment('登録日時');

            // 外部キー制約（参照整合性）
            // 職員が削除された場合は NULL を設定（監査履歴は保持）
            $table->foreign('registered_by')
                ->references('id')
                ->on('staffs')
                ->onDelete('set null');

            // 登録者での検索用インデックス
            $table->index('registered_by');
        });
    }

    /**
     * マイグレーションロールバック
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // 外部キー制約を先に削除
            $table->dropForeign(['registered_by']);

            // インデックスを削除
            $table->dropIndex(['registered_by']);

            // カラムを削除
            $table->dropColumn(['registered_by', 'registered_at']);
        });
    }
};
