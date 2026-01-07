<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * セッション管理ドメインサービス
 *
 * 職員のセッション管理を行うドメインサービス。
 * 同時ログイン制御とセッション一覧・終了機能を提供する。
 *
 * @feature 001-security-preparation
 */
final class SessionManagerService
{
    /**
     * 一般職員の同時ログイン上限
     */
    private const STAFF_SESSION_LIMIT = 3;

    /**
     * 管理者の同時ログイン上限
     */
    private const ADMIN_SESSION_LIMIT = 1;

    /**
     * セッションテーブル名
     */
    private const TABLE = 'sessions';

    /**
     * 職員のアクティブなセッション一覧を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @return Collection<int, \stdClass> セッション一覧
     */
    public function getActiveSessions(StaffId $staffId): Collection
    {
        return DB::table(self::TABLE)
            ->where('user_id', $staffId->value())
            ->orderBy('last_activity', 'desc')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);
    }

    /**
     * 指定したセッションを終了
     *
     * @param  StaffId  $staffId  職員ID（所有者確認用）
     * @param  string  $sessionId  終了するセッションID
     * @return bool 終了成功の場合 true
     */
    public function terminateSession(StaffId $staffId, string $sessionId): bool
    {
        $deleted = DB::table(self::TABLE)
            ->where('id', $sessionId)
            ->where('user_id', $staffId->value())
            ->delete();

        return $deleted > 0;
    }

    /**
     * 現在のセッション以外の全セッションを終了
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $currentSessionId  維持するセッションID
     * @return int 終了したセッション数
     */
    public function terminateOtherSessions(StaffId $staffId, string $currentSessionId): int
    {
        return DB::table(self::TABLE)
            ->where('user_id', $staffId->value())
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * 職員のセッション数を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @return int セッション数
     */
    public function getSessionCount(StaffId $staffId): int
    {
        return DB::table(self::TABLE)
            ->where('user_id', $staffId->value())
            ->count();
    }

    /**
     * 同時ログイン制限を適用
     *
     * ログイン成功時に呼び出され、上限を超える古いセッションを削除する。
     *
     * @param  StaffId  $staffId  職員ID
     * @param  bool  $isAdmin  管理者かどうか
     * @param  string  $currentSessionId  現在のセッションID
     * @return int 削除したセッション数
     */
    public function enforceSessionLimit(StaffId $staffId, bool $isAdmin, string $currentSessionId): int
    {
        $limit = $isAdmin ? self::ADMIN_SESSION_LIMIT : self::STAFF_SESSION_LIMIT;

        // 同一ユーザーの有効なセッションを取得（現在のセッションを除く、古い順）
        $sessions = DB::table(self::TABLE)
            ->where('user_id', $staffId->value())
            ->where('id', '!=', $currentSessionId)
            ->orderBy('last_activity', 'asc')
            ->get();

        // 上限を超えている場合、古いセッションを削除
        $allowedOtherSessions = $limit - 1;
        $sessionsToDelete = $sessions->count() - $allowedOtherSessions;

        if ($sessionsToDelete > 0) {
            $sessionIdsToDelete = $sessions->take($sessionsToDelete)->pluck('id');

            return DB::table(self::TABLE)
                ->whereIn('id', $sessionIdsToDelete)
                ->delete();
        }

        return 0;
    }

    /**
     * 一般職員のセッション上限を取得
     */
    public function getStaffSessionLimit(): int
    {
        return self::STAFF_SESSION_LIMIT;
    }

    /**
     * 管理者のセッション上限を取得
     */
    public function getAdminSessionLimit(): int
    {
        return self::ADMIN_SESSION_LIMIT;
    }
}
