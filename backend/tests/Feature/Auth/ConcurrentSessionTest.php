<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\ConcurrentSessionLimit;
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
 * 同時ログイン制限機能のテスト
 *
 * User Story 2: 同時ログイン制限
 * - 一般職員: 最大3台まで
 * - 管理者: 最大1台まで
 * - 上限超過時は最も古いセッションを自動削除
 */
class ConcurrentSessionTest extends TestCase
{
    use RefreshDatabase;

    private StaffRecord $staffRecord;

    private StaffRecord $adminRecord;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    // =========================================================================
    // 一般職員の同時ログイン制限テスト
    // =========================================================================

    /**
     * @test
     * 一般職員の同時ログイン上限は3であること
     */
    public function test_一般職員の同時ログイン上限は3(): void
    {
        $this->assertEquals(3, ConcurrentSessionLimit::getStaffSessionLimit());
    }

    /**
     * @test
     * 一般職員が上限内でログインできること（3セッション）
     */
    public function test_一般職員が上限内でログインできること(): void
    {
        $userId = $this->staffRecord->id;

        // 3つのセッションを作成
        for ($i = 1; $i <= 3; $i++) {
            $sessionId = "session_{$i}";
            $this->createSession($sessionId, $userId, time() + $i);

            ConcurrentSessionLimit::enforceLimit(
                userId: $userId,
                isAdmin: false,
                currentSessionId: $sessionId
            );
        }

        // 3つのセッションがすべて残っている
        $this->assertEquals(3, ConcurrentSessionLimit::getSessionCount($userId));
    }

    /**
     * @test
     * 一般職員が上限を超えると最も古いセッションが削除されること
     */
    public function test_一般職員が上限を超えると最も古いセッションが削除されること(): void
    {
        $userId = $this->staffRecord->id;

        // 3つの既存セッションを作成
        $this->createSession('session_1', $userId, time() - 300); // 最も古い
        $this->createSession('session_2', $userId, time() - 200);
        $this->createSession('session_3', $userId, time() - 100);

        // 4つ目のセッションでログイン
        $newSessionId = 'session_4';
        $this->createSession($newSessionId, $userId, time());

        ConcurrentSessionLimit::enforceLimit(
            userId: $userId,
            isAdmin: false,
            currentSessionId: $newSessionId
        );

        // セッション数は上限の3に保たれる
        $this->assertEquals(3, ConcurrentSessionLimit::getSessionCount($userId));

        // 最も古いセッション（session_1）が削除されている
        $this->assertDatabaseMissing('sessions', ['id' => 'session_1']);

        // 新しいセッションと2番目、3番目に古いセッションは残っている
        $this->assertDatabaseHas('sessions', ['id' => 'session_2']);
        $this->assertDatabaseHas('sessions', ['id' => 'session_3']);
        $this->assertDatabaseHas('sessions', ['id' => 'session_4']);
    }

    // =========================================================================
    // 管理者の同時ログイン制限テスト
    // =========================================================================

    /**
     * @test
     * 管理者の同時ログイン上限は1であること
     */
    public function test_管理者の同時ログイン上限は1(): void
    {
        $this->assertEquals(1, ConcurrentSessionLimit::getAdminSessionLimit());
    }

    /**
     * @test
     * 管理者が新規ログインすると既存セッションが削除されること
     */
    public function test_管理者が新規ログインすると既存セッションが削除されること(): void
    {
        $userId = $this->adminRecord->id;

        // 既存セッションを作成
        $this->createSession('admin_session_1', $userId, time() - 100);

        // 新しいセッションでログイン
        $newSessionId = 'admin_session_2';
        $this->createSession($newSessionId, $userId, time());

        ConcurrentSessionLimit::enforceLimit(
            userId: $userId,
            isAdmin: true,
            currentSessionId: $newSessionId
        );

        // セッション数は上限の1に保たれる
        $this->assertEquals(1, ConcurrentSessionLimit::getSessionCount($userId));

        // 古いセッションが削除されている
        $this->assertDatabaseMissing('sessions', ['id' => 'admin_session_1']);

        // 新しいセッションは残っている
        $this->assertDatabaseHas('sessions', ['id' => 'admin_session_2']);
    }

    /**
     * @test
     * 管理者が複数の既存セッションを持つ場合、すべて削除されること
     */
    public function test_管理者が複数の既存セッションを持つ場合すべて削除されること(): void
    {
        $userId = $this->adminRecord->id;

        // 複数の既存セッションを作成（異常状態を想定）
        $this->createSession('admin_session_1', $userId, time() - 300);
        $this->createSession('admin_session_2', $userId, time() - 200);
        $this->createSession('admin_session_3', $userId, time() - 100);

        // 新しいセッションでログイン
        $newSessionId = 'admin_session_4';
        $this->createSession($newSessionId, $userId, time());

        ConcurrentSessionLimit::enforceLimit(
            userId: $userId,
            isAdmin: true,
            currentSessionId: $newSessionId
        );

        // セッション数は上限の1に保たれる
        $this->assertEquals(1, ConcurrentSessionLimit::getSessionCount($userId));

        // すべての古いセッションが削除されている
        $this->assertDatabaseMissing('sessions', ['id' => 'admin_session_1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'admin_session_2']);
        $this->assertDatabaseMissing('sessions', ['id' => 'admin_session_3']);

        // 新しいセッションは残っている
        $this->assertDatabaseHas('sessions', ['id' => 'admin_session_4']);
    }

    // =========================================================================
    // 境界値テスト
    // =========================================================================

    /**
     * @test
     * セッションがない状態で新規ログインできること
     */
    public function test_セッションがない状態で新規ログインできること(): void
    {
        $userId = $this->staffRecord->id;

        // 新しいセッションでログイン
        $sessionId = 'new_session';
        $this->createSession($sessionId, $userId, time());

        ConcurrentSessionLimit::enforceLimit(
            userId: $userId,
            isAdmin: false,
            currentSessionId: $sessionId
        );

        // セッションが1つ存在する
        $this->assertEquals(1, ConcurrentSessionLimit::getSessionCount($userId));
    }

    /**
     * @test
     * 他のユーザーのセッションに影響しないこと
     */
    public function test_他のユーザーのセッションに影響しないこと(): void
    {
        $staffUserId = $this->staffRecord->id;
        $adminUserId = $this->adminRecord->id;

        // 一般職員に3つのセッションを作成
        $this->createSession('staff_session_1', $staffUserId, time() - 300);
        $this->createSession('staff_session_2', $staffUserId, time() - 200);
        $this->createSession('staff_session_3', $staffUserId, time() - 100);

        // 管理者がログイン
        $adminSessionId = 'admin_session_1';
        $this->createSession($adminSessionId, $adminUserId, time());

        ConcurrentSessionLimit::enforceLimit(
            userId: $adminUserId,
            isAdmin: true,
            currentSessionId: $adminSessionId
        );

        // 一般職員のセッションは影響を受けない
        $this->assertEquals(3, ConcurrentSessionLimit::getSessionCount($staffUserId));

        // 管理者のセッションは1つ
        $this->assertEquals(1, ConcurrentSessionLimit::getSessionCount($adminUserId));
    }

    // =========================================================================
    // ヘルパーメソッド
    // =========================================================================

    /**
     * テスト用セッションを作成
     *
     * @param  string  $sessionId  セッションID
     * @param  string  $userId  ユーザーID
     * @param  int  $lastActivity  最終アクティビティ時刻
     */
    private function createSession(string $sessionId, string $userId, int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'payload' => base64_encode(serialize([])),
            'last_activity' => $lastActivity,
        ]);
    }
}
