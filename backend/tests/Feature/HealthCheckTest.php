<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * ヘルスチェック API のテストクラス
 *
 * バックエンドサービスとデータベース接続の正常性を確認するテスト
 */
class HealthCheckTest extends TestCase
{
    /**
     * /api/health エンドポイントが正常なレスポンスを返すことを確認
     */
    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'laravel_version',
            ]);
    }

    /**
     * /api/health/db エンドポイントがデータベース接続状態を返すことを確認
     *
     * 注意: テスト環境ではデータベース接続が異なる場合があるため、
     * 接続タイプではなく構造のみを検証
     */
    public function test_health_db_endpoint_returns_connection_info(): void
    {
        $response = $this->getJson('/api/health/db');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ])
            ->assertJsonStructure([
                'status',
                'connection',
                'database',
            ]);
    }
}
