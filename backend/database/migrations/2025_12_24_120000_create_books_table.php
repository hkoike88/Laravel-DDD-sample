<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 蔵書テーブルを作成するマイグレーション
 *
 * 蔵書エンティティの永続化用テーブル。
 * ULID を主キーとして使用し、検索用インデックスを設定。
 */
return new class extends Migration
{
    /**
     * 蔵書テーブルを作成
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // 主キー（ULID 26文字）
            $table->string('id', 26)->primary();

            // 書誌情報
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 13)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('genre', 100)->nullable();

            // 状態管理
            $table->string('status', 20)->default('available');

            // タイムスタンプ
            $table->timestamps();

            // インデックス
            $table->index('isbn');
            $table->index('status');
            $table->index('genre');
            $table->index('published_year');
        });

        // 部分インデックス（タイトル・著者の先頭100文字）- MySQLのみ
        // SQLite はプレフィックスインデックスをサポートしないため条件分岐
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('CREATE INDEX idx_books_title ON books (title(100))');
            DB::statement('CREATE INDEX idx_books_author ON books (author(100))');
        } else {
            // SQLite や他のDBでは通常のインデックスを使用
            Schema::table('books', function (Blueprint $table) {
                $table->index('title', 'idx_books_title');
                $table->index('author', 'idx_books_author');
            });
        }
    }

    /**
     * 蔵書テーブルを削除
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
