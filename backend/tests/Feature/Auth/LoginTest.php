<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * ログイン API の Feature テスト
 *
 * POST /api/auth/login エンドポイントの統合テスト
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しい認証情報でログインできること
     */
    public function test_successful_login_with_valid_credentials(): void
    {
        // Arrange: テスト用職員を作成
        $staff = StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        // Act: セッション付きでログインリクエスト
        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: 成功レスポンス
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
                    'email' => 'test@example.com',
                    'name' => '山田太郎',
                ],
            ]);
    }

    /**
     * 存在しないメールアドレスでログイン失敗すること
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => '認証情報が正しくありません',
            ]);
    }

    /**
     * 間違ったパスワードでログイン失敗すること
     */
    public function test_login_fails_with_wrong_password(): void
    {
        // Arrange
        StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('correct_password'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => '認証情報が正しくありません',
            ]);

        // 失敗カウントがインクリメントされていること
        $this->assertDatabaseHas('staffs', [
            'email' => 'test@example.com',
            'failed_login_attempts' => 1,
        ]);
    }

    /**
     * ロックされたアカウントでログイン失敗すること
     */
    public function test_login_fails_with_locked_account(): void
    {
        // Arrange: ロック済みアカウント
        StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'name' => '山田太郎',
            'is_locked' => true,
            'failed_login_attempts' => 5,
            'locked_at' => new DateTimeImmutable,
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(423)
            ->assertJson([
                'message' => 'アカウントがロックされています',
            ]);
    }

    /**
     * 5回失敗でアカウントがロックされること
     */
    public function test_account_locks_after_5_failed_attempts(): void
    {
        // Arrange: 4回失敗済み
        StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('correct_password'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 4,
            'locked_at' => null,
        ]);

        // Act: 5回目の失敗
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        // Assert
        $response->assertStatus(401);

        // アカウントがロックされていること
        $this->assertDatabaseHas('staffs', [
            'email' => 'test@example.com',
            'is_locked' => true,
            'failed_login_attempts' => 5,
        ]);
    }

    /**
     * レート制限が適用されること（5回/分）
     */
    public function test_rate_limiting_applies(): void
    {
        // 6回連続でリクエストを送信
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);
        }

        // 6回目はレート制限
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(429);
    }

    /**
     * メールアドレスが空の場合はバリデーションエラー
     */
    public function test_validation_fails_with_empty_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * パスワードが空の場合はバリデーションエラー
     */
    public function test_validation_fails_with_empty_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * メールアドレス形式が不正な場合はバリデーションエラー
     */
    public function test_validation_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * ログイン成功後にセッションが確立されていること
     */
    public function test_session_is_established_after_successful_login(): void
    {
        // Arrange
        StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        // Act
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: 認証済みユーザーを取得できること
        $this->assertAuthenticated('web');
    }

    /**
     * ログイン成功後に失敗カウントがリセットされること
     */
    public function test_failed_attempts_reset_after_successful_login(): void
    {
        // Arrange: 失敗カウントが2の状態
        StaffRecord::create([
            'id' => '01JFGXYZ123456789ABCDEFGH',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'name' => '山田太郎',
            'is_locked' => false,
            'failed_login_attempts' => 2,
            'locked_at' => null,
        ]);

        // Act: 成功するログイン
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: 失敗カウントがリセットされていること
        $this->assertDatabaseHas('staffs', [
            'email' => 'test@example.com',
            'failed_login_attempts' => 0,
        ]);
    }
}
