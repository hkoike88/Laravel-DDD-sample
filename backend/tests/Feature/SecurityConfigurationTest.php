<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * セキュリティ設定検証テスト
 *
 * システムのセキュリティ関連設定が適切であることを検証する。
 * 設定ファイルの値を直接検証し、本番環境での設定が正しいことを確認。
 *
 * @feature 001-security-preparation
 */
class SecurityConfigurationTest extends TestCase
{
    /**
     * bcrypt のコストデフォルト値が12であることを検証
     *
     * NIST SP 800-63B に準拠
     */
    public function test_bcrypt_cost_default_is_12(): void
    {
        // 設定ファイルの内容を直接確認
        $content = file_get_contents(base_path('config/hashing.php'));

        // env('BCRYPT_ROUNDS', 12) のデフォルト値が12であることを確認
        $this->assertStringContainsString(
            "env('BCRYPT_ROUNDS', 12)",
            $content,
            'bcrypt cost default should be 12 for NIST SP 800-63B compliance'
        );
    }

    /**
     * セッションの暗号化デフォルト値が有効であることを検証
     */
    public function test_session_encryption_default_is_enabled(): void
    {
        $config = require base_path('config/session.php');

        // env('SESSION_ENCRYPT', true) のデフォルト値が true であることを確認
        // 設定ファイルでは env() を使用しているため、第2引数のデフォルト値を確認
        $content = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_ENCRYPT', true)",
            $content,
            'Session encryption default should be true'
        );
    }

    /**
     * セッション Cookie が HTTP Only デフォルトであることを検証
     */
    public function test_session_cookie_http_only_default_is_enabled(): void
    {
        $content = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_HTTP_ONLY', true)",
            $content,
            'Session cookie HTTP only default should be true'
        );
    }

    /**
     * セッション Cookie の SameSite デフォルト値が設定されていることを検証
     */
    public function test_session_cookie_same_site_default_is_lax(): void
    {
        $content = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_SAME_SITE', 'lax')",
            $content,
            'Session cookie SameSite default should be lax'
        );
    }

    /**
     * セッションのデフォルトアイドルタイムアウトが30分であることを検証
     */
    public function test_session_idle_timeout_default_is_30_minutes(): void
    {
        $content = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_LIFETIME', 30)",
            $content,
            'Session idle timeout default should be 30 minutes'
        );
    }

    /**
     * セッションドライバーのデフォルトがデータベースであることを検証
     */
    public function test_session_driver_default_is_database(): void
    {
        $content = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_DRIVER', 'database')",
            $content,
            'Session driver default should be database for proper session management'
        );
    }

    /**
     * セキュリティログチャンネルが設定されていることを検証
     */
    public function test_security_log_channel_is_configured(): void
    {
        $config = require base_path('config/logging.php');

        $this->assertArrayHasKey(
            'security',
            $config['channels'],
            'Security log channel should be configured'
        );

        $this->assertEquals(
            'daily',
            $config['channels']['security']['driver'],
            'Security log channel should use daily driver for log rotation'
        );

        $this->assertEquals(
            365,
            $config['channels']['security']['days'],
            'Security logs should be retained for 365 days'
        );
    }

    /**
     * パスワード履歴の保持期間が適切であることを検証
     */
    public function test_password_history_generations(): void
    {
        $expectedGenerations = 5;

        $reflection = new \ReflectionClass(\App\Rules\PasswordNotReusedRule::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey(
            'HISTORY_COUNT',
            $constants,
            'PasswordNotReusedRule should have HISTORY_COUNT constant'
        );

        $this->assertEquals(
            $expectedGenerations,
            $constants['HISTORY_COUNT'],
            'Password history should retain 5 generations'
        );
    }

    /**
     * セッション Secure Cookie デフォルト値が有効であることを検証
     */
    public function test_session_secure_cookie_default_is_enabled(): void
    {
        $content = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            "env('SESSION_SECURE_COOKIE', true)",
            $content,
            'Session secure cookie default should be true'
        );
    }
}
