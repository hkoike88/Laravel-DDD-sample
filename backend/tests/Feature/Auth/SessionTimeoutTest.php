<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\AbsoluteSessionTimeout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * セッションタイムアウト機能のテスト
 *
 * User Story 1: セッション自動タイムアウト
 * - アイドルタイムアウト（30分）- Laravel セッション設定で処理
 * - 絶対タイムアウト（8時間）- AbsoluteSessionTimeout ミドルウェアで処理
 */
class SessionTimeoutTest extends TestCase
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
    // アイドルタイムアウト（30分）テスト
    // =========================================================================

    /**
     * @test
     * アイドルタイムアウト前はアクセス可能
     */
    public function test_アイドルタイムアウト前はアクセス可能(): void
    {
        // ログイン
        $this->actingAs($this->staffRecord);

        // 認証済みエンドポイントにアクセス
        $response = $this->getJson('/api/auth/user');

        $response->assertOk();
    }

    // =========================================================================
    // 絶対タイムアウト（8時間）テスト - ミドルウェアユニットテスト
    // =========================================================================

    /**
     * @test
     * 絶対タイムアウト前はアクセス可能（ミドルウェア経由）
     */
    public function test_絶対タイムアウト前はミドルウェアを通過する(): void
    {
        // セッション作成日時を設定（7時間59分前）
        $request = $this->createRequestWithSession(time() - (7 * 60 * 60 + 59 * 60));

        $middleware = new AbsoluteSessionTimeout;
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     * 絶対タイムアウト後は401エラーが返る（ミドルウェア経由）
     */
    public function test_絶対タイムアウト後は401エラーを返す(): void
    {
        // セッション作成日時を設定（8時間1秒前）
        $request = $this->createRequestWithSession(time() - (8 * 60 * 60 + 1));

        $middleware = new AbsoluteSessionTimeout;
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('セッションがタイムアウトしました。再度ログインしてください', $data['message']);
    }

    /**
     * @test
     * 絶対タイムアウト境界値（ちょうど8時間）で401エラーが返る
     */
    public function test_絶対タイムアウト境界値で401エラーを返す(): void
    {
        // セッション作成日時を設定（ちょうど8時間前）
        $request = $this->createRequestWithSession(time() - (8 * 60 * 60));

        $middleware = new AbsoluteSessionTimeout;
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     * セッション作成日時が未設定でもミドルウェアを通過する
     */
    public function test_セッション作成日時が未設定でも通過する(): void
    {
        // セッション作成日時を設定しない
        $request = $this->createRequestWithSession(null);

        $middleware = new AbsoluteSessionTimeout;
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    // =========================================================================
    // ログイン時のセッション作成日時設定テスト
    // =========================================================================

    /**
     * @test
     * ログイン API が正常に動作する
     */
    public function test_ログイン_ap_iが正常に動作する(): void
    {
        // CSRF トークン取得
        $this->get('/sanctum/csrf-cookie');

        // ログイン
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'email',
                'name',
            ],
        ]);
    }

    // =========================================================================
    // ヘルパーメソッド
    // =========================================================================

    /**
     * セッション付きのリクエストを作成
     *
     * @param  int|null  $sessionCreatedAt  セッション作成日時（null の場合は未設定）
     */
    private function createRequestWithSession(?int $sessionCreatedAt): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create('/api/auth/user', 'GET');

        // セッションストアを設定
        $sessionStore = app('session.store');

        if ($sessionCreatedAt !== null) {
            $sessionStore->put(AbsoluteSessionTimeout::getSessionCreatedAtKey(), $sessionCreatedAt);
        }

        $request->setLaravelSession($sessionStore);

        // 認証ユーザーを設定（Auth::check() が true を返すように）
        $this->actingAs($this->staffRecord);

        return $request;
    }
}
