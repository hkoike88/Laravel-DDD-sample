<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * 認証済み職員情報取得 API の Feature テスト
 *
 * GET /api/auth/user エンドポイントの統合テスト
 */
class GetCurrentUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーが自身の情報を取得できること
     */
    public function test_authenticated_user_can_get_own_info(): void
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

        // Act: 認証済みリクエスト
        $response = $this->actingAs($staff)->getJson('/api/auth/user');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => '01JFGXYZ123456789ABCDEFGH',
                    'email' => 'test@example.com',
                    'name' => '山田太郎',
                ],
            ]);
    }

    /**
     * 未認証ユーザーは 401 エラーが返されること
     */
    public function test_unauthenticated_user_gets_401_error(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }
}
