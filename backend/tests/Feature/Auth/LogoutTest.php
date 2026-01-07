<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * ログアウト API の Feature テスト
 *
 * POST /api/auth/logout エンドポイントの統合テスト
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログアウトが成功すること
     */
    public function test_successful_logout(): void
    {
        // Arrange
        $staff = StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        // Act: 認証済み状態でログアウト
        $response = $this->actingAs($staff)->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'ログアウトしました',
            ]);
    }

    /**
     * ログアウトレスポンスが正しいこと
     */
    public function test_logout_response_is_correct(): void
    {
        // Arrange
        $staff = StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        // 認証済み状態でログアウト
        $response = $this->actingAs($staff)->postJson('/api/auth/logout');

        // レスポンスが正しいこと
        $response->assertStatus(200)
            ->assertJsonStructure(['message']);
    }

    /**
     * 未認証ユーザーは 401 エラーが返されること
     */
    public function test_unauthenticated_user_gets_401_error(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}
