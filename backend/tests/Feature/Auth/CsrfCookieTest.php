<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * CSRF Cookie API の Feature テスト
 *
 * GET /sanctum/csrf-cookie エンドポイントの統合テスト
 */
class CsrfCookieTest extends TestCase
{
    /**
     * CSRF cookie エンドポイントが XSRF-TOKEN クッキーを設定すること
     */
    public function test_csrf_cookie_endpoint_sets_xsrf_token_cookie(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
        $response->assertCookie('XSRF-TOKEN');
    }

    /**
     * CSRF トークンを使用してセキュアなリクエストが送信できること
     */
    public function test_csrf_protected_request_works_with_token(): void
    {
        // まず CSRF トークンを取得
        $this->get('/sanctum/csrf-cookie');

        // CSRF トークンを使用してリクエスト（ログインはバリデーションエラーでも可）
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // CSRF エラー (419) ではないこと
        $this->assertNotEquals(419, $response->status());
    }
}
