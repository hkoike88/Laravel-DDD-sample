<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * ヘルスチェックコントローラー
 * バックエンドサービスとデータベースの稼働状態を確認するAPIを提供
 */
class HealthController extends Controller
{
    /**
     * 基本的なヘルスチェック
     * サービスの稼働状態とLaravelバージョンを返す
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'laravel_version' => app()->version(),
        ]);
    }

    /**
     * データベースヘルスチェック
     * MySQLへの接続状態を確認する
     */
    public function database(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            /** @var string $connectionName */
            $connectionName = config('database.default');

            /** @var string $databaseName */
            $databaseName = config("database.connections.{$connectionName}.database");

            return response()->json([
                'status' => 'ok',
                'connection' => $connectionName,
                'database' => $databaseName,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database connection failed',
            ], 503);
        }
    }
}
