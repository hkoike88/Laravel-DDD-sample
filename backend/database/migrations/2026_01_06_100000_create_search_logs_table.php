<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 検索統計ログテーブルのマイグレーション
 *
 * 蔵書検索の統計情報を匿名で記録するためのテーブルを作成。
 * 検索傾向分析や人気キーワードの把握に使用。
 */
return new class extends Migration
{
    /**
     * マイグレーション実行
     */
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            // ULID形式の主キー
            $table->char('id', 26)->primary();

            // 結合された検索キーワード（分析用）
            $table->string('keyword', 255);

            // 個別の検索条件
            $table->string('title_keyword', 255)->nullable();
            $table->string('author_keyword', 255)->nullable();
            $table->string('isbn_keyword', 13)->nullable();

            // 検索結果件数
            $table->unsignedInteger('result_count');

            // 検索実行日時
            $table->timestamp('searched_at');

            // インデックス
            $table->index('searched_at');
            $table->index('keyword');
        });
    }

    /**
     * マイグレーションロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
