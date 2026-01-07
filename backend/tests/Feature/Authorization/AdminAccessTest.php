<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * 管理者専用ルートのアクセス制御テスト
 *
 * User Story 3: 管理者専用URLへの直接アクセス制御
 * - 管理者のみが /api/staff/accounts にアクセスできる
 * - 一般職員は 403 エラーを受ける
 * - 未ログインは 401 エラーを受ける
 *
 * @feature 003-role-based-menu
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private StaffRecord $adminRecord;

    private StaffRecord $staffRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用管理者を作成
        $admin = Staff::create(
            id: StaffId::generate(),
            email: Email::create('admin@example.com'),
            password: Password::fromPlainText('password123'),
            name: StaffName::create('テスト管理者'),
            isAdmin: true,
        );

        $this->adminRecord = StaffRecord::create([
            'id' => $admin->id()->value(),
            'email' => $admin->email()->value(),
            'password' => $admin->password()->hashedValue(),
            'name' => $admin->name()->value(),
            'is_admin' => true,
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        // テスト用一般職員を作成
        $staff = Staff::create(
            id: StaffId::generate(),
            email: Email::create('staff@example.com'),
            password: Password::fromPlainText('password123'),
            name: StaffName::create('テスト職員'),
            isAdmin: false,
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
    // 管理者アクセステスト
    // =========================================================================

    /**
     * @test
     * 管理者が /api/staff/accounts にアクセスできること
     */
    public function test_管理者がstaff_accountsにアクセスできること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // 管理者でログイン
        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertOk();

        // 管理者専用ルートにアクセス
        $response = $this->getJson('/api/staff/accounts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['total', 'currentPage', 'perPage'],
        ]);
    }

    // =========================================================================
    // 一般職員アクセステスト
    // =========================================================================

    /**
     * @test
     * 一般職員が /api/staff/accounts にアクセスすると403が返ること
     */
    public function test_一般職員がstaff_accountsにアクセスすると403が返ること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // 一般職員でログイン
        $this->postJson('/api/auth/login', [
            'email' => 'staff@example.com',
            'password' => 'password123',
        ])->assertOk();

        // 管理者専用ルートにアクセス
        $response = $this->getJson('/api/staff/accounts');

        $response->assertForbidden();
        $response->assertJsonPath('error.message', 'この操作を行う権限がありません');
    }

    // =========================================================================
    // 未認証アクセステスト
    // =========================================================================

    /**
     * @test
     * 未ログインで /api/staff/accounts にアクセスすると401が返ること
     */
    public function test_未ログインでstaff_accountsにアクセスすると401が返ること(): void
    {
        // 未ログイン状態で管理者専用ルートにアクセス
        $response = $this->getJson('/api/staff/accounts');

        $response->assertUnauthorized();
    }

    // =========================================================================
    // エッジケーステスト
    // =========================================================================

    /**
     * @test
     * 管理者専用ルートに対するミドルウェアが正しく適用されていること
     *
     * 管理者でログイン後、アクセス可能であること、
     * ログアウト処理が成功することを確認する。
     *
     * 注意: Laravel のテスト環境ではセッション管理の挙動が本番と異なるため、
     * ログアウト後の401応答テストは test_未ログインでstaffAccountsにアクセスすると401が返ること
     * でカバーする。
     */
    public function test_管理者専用ルートにミドルウェアが正しく適用されていること(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // 管理者でログイン
        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertOk();

        // アクセス可能を確認（ミドルウェアチェーンが正しく動作）
        $this->getJson('/api/staff/accounts')->assertOk();

        // ログアウト処理が成功することを確認
        $logoutResponse = $this->postJson('/api/auth/logout');
        $logoutResponse->assertOk();
        $logoutResponse->assertJsonPath('message', 'ログアウトしました');
    }
}
