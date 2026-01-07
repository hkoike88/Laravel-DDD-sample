<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * セッション永続化機能のテスト
 *
 * User Story 3: セッション永続化
 * - 本番環境ではセッション情報がデータベースに保存される
 * - テスト環境では array ドライバを使用（パフォーマンス向上のため）
 *
 * 注意: テスト環境は SESSION_DRIVER=array を使用するため、
 * データベース永続化の実際の動作は .env と config/session.php で設定確認
 */
class SessionPersistenceTest extends TestCase
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
    // データベース設定確認テスト
    // =========================================================================

    /**
     * @test
     * sessions テーブルが存在すること（データベース永続化の準備完了）
     */
    public function test_sessionsテーブルが存在すること(): void
    {
        $exists = DB::getSchemaBuilder()->hasTable('sessions');

        $this->assertTrue($exists);
    }

    /**
     * @test
     * sessions テーブルに ULID 対応の user_id カラムが存在すること
     */
    public function test_sessionsテーブルにuser_idカラムが存在すること(): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('sessions');

        $this->assertContains('id', $columns);
        $this->assertContains('user_id', $columns);
        $this->assertContains('ip_address', $columns);
        $this->assertContains('user_agent', $columns);
        $this->assertContains('payload', $columns);
        $this->assertContains('last_activity', $columns);
    }

    // =========================================================================
    // config/session.php 設定確認テスト（ファイル直接読み込み）
    // =========================================================================

    /**
     * @test
     * session.php でデータベースがデフォルトドライバとして指定されていること
     */
    public function test_sessionphpでデータベースがデフォルトドライバとして指定されていること(): void
    {
        // config/session.php のソースコードを読み込み
        $sessionConfigPath = base_path('config/session.php');
        $content = file_get_contents($sessionConfigPath);

        // env('SESSION_DRIVER', 'database') の形式で database がデフォルトになっていることを確認
        $this->assertStringContainsString("env('SESSION_DRIVER', 'database')", $content);
    }

    /**
     * @test
     * session.php でセッション暗号化のデフォルトが true であること
     */
    public function test_sessionphpでセッション暗号化のデフォルトがtrueであること(): void
    {
        $sessionConfigPath = base_path('config/session.php');
        $content = file_get_contents($sessionConfigPath);

        // env('SESSION_ENCRYPT', true) の形式で true がデフォルトになっていることを確認
        $this->assertStringContainsString("env('SESSION_ENCRYPT', true)", $content);
    }

    /**
     * @test
     * session.php でセッションライフタイムのデフォルトが30分であること
     */
    public function test_sessionphpでセッションライフタイムのデフォルトが30分であること(): void
    {
        $sessionConfigPath = base_path('config/session.php');
        $content = file_get_contents($sessionConfigPath);

        // env('SESSION_LIFETIME', 30) の形式で 30 がデフォルトになっていることを確認
        $this->assertStringContainsString("env('SESSION_LIFETIME', 30)", $content);
    }

    /**
     * @test
     * session.php でセキュアクッキーのデフォルトが true であること
     */
    public function test_sessionphpでセキュアクッキーのデフォルトがtrueであること(): void
    {
        $sessionConfigPath = base_path('config/session.php');
        $content = file_get_contents($sessionConfigPath);

        // env('SESSION_SECURE_COOKIE', true) の形式で true がデフォルトになっていることを確認
        $this->assertStringContainsString("env('SESSION_SECURE_COOKIE', true)", $content);
    }

    /**
     * @test
     * session.php で HTTP-only クッキーのデフォルトが true であること
     */
    public function test_sessionphpで_http_onlyクッキーのデフォルトがtrueであること(): void
    {
        $sessionConfigPath = base_path('config/session.php');
        $content = file_get_contents($sessionConfigPath);

        // env('SESSION_HTTP_ONLY', true) の形式で true がデフォルトになっていることを確認
        $this->assertStringContainsString("env('SESSION_HTTP_ONLY', true)", $content);
    }

    /**
     * @test
     * session.php で SameSite 属性のデフォルトが lax であること
     */
    public function test_sessionphpで_same_site属性のデフォルトがlaxであること(): void
    {
        $sessionConfigPath = base_path('config/session.php');
        $content = file_get_contents($sessionConfigPath);

        // env('SESSION_SAME_SITE', 'lax') の形式で lax がデフォルトになっていることを確認
        $this->assertStringContainsString("env('SESSION_SAME_SITE', 'lax')", $content);
    }

    // =========================================================================
    // セッション動作テスト
    // =========================================================================

    /**
     * @test
     * 認証済みリクエストでセッション情報が維持されること
     */
    public function test_認証済みリクエストでセッション情報が維持されること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();

        // 認証済みエンドポイントにアクセス
        $userResponse = $this->getJson('/api/auth/user');

        $userResponse->assertOk();
        $userResponse->assertJsonPath('data.email', 'test@example.com');
    }

    /**
     * @test
     * 複数リクエストでセッションが維持されること
     */
    public function test_複数リクエストでセッションが維持されること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ])->assertOk();

        // 複数回リクエスト
        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson('/api/auth/user');
            $response->assertOk();
            $response->assertJsonPath('data.email', 'test@example.com');
        }
    }

    /**
     * @test
     * ログアウト API が正常に動作すること
     */
    public function test_ログアウト_ap_iが正常に動作すること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ])->assertOk();

        // 認証済みであることを確認
        $this->getJson('/api/auth/user')->assertOk();

        // ログアウト
        $response = $this->postJson('/api/auth/logout');

        $response->assertOk();
        $response->assertJsonPath('message', 'ログアウトしました');
    }
}
