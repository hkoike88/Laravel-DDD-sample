<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * CSRF 保護機能のテスト
 *
 * User Story 4: CSRF 保護
 * - CSRF トークンありでのリクエストが成功すること
 * - CSRF トークンなしでのリクエストが拒否されること
 *
 * 注意: Laravel テスト環境では CSRF 検証がデフォルトで無効化されるため、
 * 設定ファイルの確認と Sanctum 設定のテストを行う
 */
class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    private StaffRecord $staffRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用職員を作成
        $staff = Staff::create(
            id: StaffId::generate(),
            email: Email::create('test@example.com'),
            password: Password::fromPlainText('password123'),
            name: StaffName::create('テスト職員'),
        );

        $this->staffRecord = StaffRecord::create([
            'id' => $staff->id()->value(),
            'email' => $staff->email()->value(),
            'password' => $staff->password()->hashedValue(),
            'name' => $staff->name()->value(),
            'is_admin' => false,
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);
    }

    // =========================================================================
    // Sanctum 設定確認テスト
    // =========================================================================

    /**
     * @test
     * Sanctum 設定に CSRF 検証ミドルウェアが含まれていること
     */
    public function test_sanctum設定に_csrf検証ミドルウェアが含まれていること(): void
    {
        $sanctumConfig = config('sanctum.middleware');

        $this->assertArrayHasKey('validate_csrf_token', $sanctumConfig);
        $this->assertEquals(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            $sanctumConfig['validate_csrf_token']
        );
    }

    /**
     * @test
     * Sanctum 設定に Cookie 暗号化ミドルウェアが含まれていること
     */
    public function test_sanctum設定に_cookie暗号化ミドルウェアが含まれていること(): void
    {
        $sanctumConfig = config('sanctum.middleware');

        $this->assertArrayHasKey('encrypt_cookies', $sanctumConfig);
        $this->assertEquals(
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            $sanctumConfig['encrypt_cookies']
        );
    }

    /**
     * @test
     * Sanctum 設定にセッション認証ミドルウェアが含まれていること
     */
    public function test_sanctum設定にセッション認証ミドルウェアが含まれていること(): void
    {
        $sanctumConfig = config('sanctum.middleware');

        $this->assertArrayHasKey('authenticate_session', $sanctumConfig);
        $this->assertEquals(
            \Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
            $sanctumConfig['authenticate_session']
        );
    }

    /**
     * @test
     * Sanctum のステートフルドメインが設定されていること
     */
    public function test_sanctumのステートフルドメインが設定されていること(): void
    {
        $statefulDomains = config('sanctum.stateful');

        $this->assertIsArray($statefulDomains);
        $this->assertNotEmpty($statefulDomains);

        // localhost が含まれていることを確認
        $this->assertTrue(
            collect($statefulDomains)->contains(function ($domain) {
                return str_contains($domain, 'localhost');
            })
        );
    }

    // =========================================================================
    // CSRF トークン取得テスト
    // =========================================================================

    /**
     * @test
     * CSRF クッキーエンドポイントにアクセスできること
     */
    public function test_csrfクッキーエンドポイントにアクセスできること(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
    }

    /**
     * @test
     * CSRF クッキーエンドポイントが XSRF-TOKEN クッキーを設定すること
     */
    public function test_csrfクッキーエンドポイントが_xsrf_tokenクッキーを設定すること(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
        $response->assertCookie('XSRF-TOKEN');
    }

    // =========================================================================
    // 正常なリクエストフローテスト
    // =========================================================================

    /**
     * @test
     * CSRF トークン取得後にログインできること
     */
    public function test_csrfトークンをセットしてログインできること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.email', 'test@example.com');
    }

    /**
     * @test
     * 認証後に保護されたエンドポイントにアクセスできること
     */
    public function test_認証後に保護されたエンドポイントにアクセスできること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ])->assertOk();

        // 保護されたエンドポイントにアクセス
        $response = $this->getJson('/api/auth/user');

        $response->assertOk();
        $response->assertJsonPath('data.email', 'test@example.com');
    }

    /**
     * @test
     * ログアウトが正常に動作すること
     */
    public function test_ログアウトが正常に動作すること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ])->assertOk();

        // ログアウト
        $response = $this->postJson('/api/auth/logout');

        $response->assertOk();
        $response->assertJsonPath('message', 'ログアウトしました');
    }

    // =========================================================================
    // bootstrap/app.php 設定確認テスト
    // =========================================================================

    /**
     * @test
     * API ミドルウェアグループに Sanctum ミドルウェアが含まれていること
     */
    public function test_ap_iミドルウェアグループに_sanctumミドルウェアが含まれていること(): void
    {
        // bootstrap/app.php のソースコードを読み込み
        $appConfigPath = base_path('bootstrap/app.php');
        $content = file_get_contents($appConfigPath);

        // EnsureFrontendRequestsAreStateful が設定されていることを確認
        $this->assertStringContainsString(
            'EnsureFrontendRequestsAreStateful',
            $content,
            'Sanctum の EnsureFrontendRequestsAreStateful ミドルウェアが API グループに設定されている必要があります'
        );
    }

    // =========================================================================
    // フロントエンド設定確認テスト
    // =========================================================================

    /**
     * @test
     * フロントエンドの axios 設定で withCredentials が有効であること
     */
    public function test_フロントエンドのaxios設定でwith_credentialsが有効であること(): void
    {
        // Docker開発環境: /var/www/frontend、ローカル: ../frontend
        $axiosPath = '/var/www/frontend/src/lib/axios.ts';
        if (! file_exists($axiosPath)) {
            $axiosPath = base_path('../frontend/src/lib/axios.ts');
        }

        if (! file_exists($axiosPath)) {
            $this->markTestSkipped('frontend/src/lib/axios.ts が見つかりません（docker-compose.develop.yml を使用してください）');
        }

        $content = file_get_contents($axiosPath);

        // withCredentials: true が設定されていることを確認
        $this->assertStringContainsString(
            'withCredentials: true',
            $content,
            'Axios に withCredentials: true が設定されている必要があります'
        );

        // withXSRFToken: true が設定されていることを確認
        $this->assertStringContainsString(
            'withXSRFToken: true',
            $content,
            'Axios に withXSRFToken: true が設定されている必要があります'
        );
    }

    /**
     * @test
     * フロントエンドの認証 API でログイン前に CSRF トークンを取得すること
     */
    public function test_フロントエンドの認証_ap_iでログイン前に_csrfトークンを取得すること(): void
    {
        // Docker開発環境: /var/www/frontend、ローカル: ../frontend
        $authApiPath = '/var/www/frontend/src/features/auth/api/authApi.ts';
        if (! file_exists($authApiPath)) {
            $authApiPath = base_path('../frontend/src/features/auth/api/authApi.ts');
        }

        if (! file_exists($authApiPath)) {
            $this->markTestSkipped('frontend/src/features/auth/api/authApi.ts が見つかりません（docker-compose.develop.yml を使用してください）');
        }

        $content = file_get_contents($authApiPath);

        // getCsrfToken が呼び出されていることを確認
        $this->assertStringContainsString(
            'getCsrfToken',
            $content,
            'ログイン前に getCsrfToken() を呼び出す必要があります'
        );
    }
}
