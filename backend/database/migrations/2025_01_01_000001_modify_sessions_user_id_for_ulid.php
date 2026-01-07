<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * sessions テーブルの user_id カラムを ULID 対応に修正
 *
 * Laravel 標準の sessions テーブルは foreignId (bigint) で user_id を定義しているが、
 * Staff エンティティは ULID (26文字の文字列) を使用しているため、カラム型を変更する。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite ではカラム型変更ができないため、一度削除して再作成
        if (DB::getDriverName() === 'sqlite') {
            return; // SQLite はテスト用なので user_id の型は無視
        }

        Schema::table('sessions', function (Blueprint $table) {
            // 既存の user_id カラムを削除
            $table->dropIndex(['user_id']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            // ULID 対応の user_id カラムに変更
            $table->string('user_id', 26)->nullable()->change();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->index('user_id');
        });
    }
};
