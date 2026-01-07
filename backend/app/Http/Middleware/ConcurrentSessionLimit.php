<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 同時ログイン制限ミドルウェア
 *
 * ユーザーの同時ログイン数を制限する。
 * - 一般職員: 最大3台
 * - 管理者: 最大1台
 *
 * 上限を超えた場合、最も古いセッションを自動的に削除する。
 */
class ConcurrentSessionLimit
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
     * リクエストを処理
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * ログイン後の処理
     *
     * ログイン成功時に呼び出され、同時ログイン数を制限する。
     *
     * @param  string  $userId  ユーザーID
     * @param  bool  $isAdmin  管理者かどうか
     * @param  string  $currentSessionId  現在のセッションID
     */
    public static function enforceLimit(string $userId, bool $isAdmin, string $currentSessionId): void
    {
        $limit = $isAdmin ? self::ADMIN_SESSION_LIMIT : self::STAFF_SESSION_LIMIT;

        // 同一ユーザーの有効なセッションを取得（現在のセッションを除く、古い順）
        $sessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->orderBy('last_activity', 'asc')
            ->get();

        // 上限を超えている場合、古いセッションを削除
        // 上限 - 1 = 他のセッションの許容数（現在のセッションを含めて上限に収める）
        $allowedOtherSessions = $limit - 1;
        $sessionsToDelete = $sessions->count() - $allowedOtherSessions;

        if ($sessionsToDelete > 0) {
            $sessionIdsToDelete = $sessions->take($sessionsToDelete)->pluck('id');

            DB::table('sessions')
                ->whereIn('id', $sessionIdsToDelete)
                ->delete();
        }
    }

    /**
     * ユーザーの現在のセッション数を取得
     *
     * @param  string  $userId  ユーザーID
     * @return int セッション数
     */
    public static function getSessionCount(string $userId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * 一般職員のセッション上限を取得
     */
    public static function getStaffSessionLimit(): int
    {
        return self::STAFF_SESSION_LIMIT;
    }

    /**
     * 管理者のセッション上限を取得
     */
    public static function getAdminSessionLimit(): int
    {
        return self::ADMIN_SESSION_LIMIT;
    }
}
