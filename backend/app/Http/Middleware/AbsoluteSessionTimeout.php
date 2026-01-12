<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SecurityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 絶対タイムアウトミドルウェア
 *
 * セッション作成から一定時間（8時間）経過後に強制ログアウトする。
 * アイドルタイムアウト（30分）とは別に、セッションの最大有効期間を制限する。
 */
class AbsoluteSessionTimeout
{
    /**
     * セッションの絶対タイムアウト時間（秒）
     * 8時間 = 8 * 60 * 60 = 28800秒
     */
    private const ABSOLUTE_TIMEOUT_SECONDS = 28800;

    /**
     * セッション作成日時のキー
     */
    private const SESSION_CREATED_AT_KEY = 'session_created_at';

    /**
     * リクエストを処理
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldCheck($request)) {
            return $next($request);
        }

        if ($this->isSessionExpired($request)) {
            return $this->handleExpiredSession($request);
        }

        return $next($request);
    }

    /**
     * タイムアウトチェックが必要かどうか
     */
    private function shouldCheck(Request $request): bool
    {
        return Auth::check() && $request->hasSession();
    }

    /**
     * セッションが期限切れかどうか
     */
    private function isSessionExpired(Request $request): bool
    {
        /** @var int|null $sessionCreatedAt */
        $sessionCreatedAt = $request->session()->get(self::SESSION_CREATED_AT_KEY);

        if ($sessionCreatedAt === null) {
            return false;
        }

        $elapsedSeconds = time() - $sessionCreatedAt;

        return $elapsedSeconds >= self::ABSOLUTE_TIMEOUT_SECONDS;
    }

    /**
     * 期限切れセッションを処理
     */
    private function handleExpiredSession(Request $request): Response
    {
        $this->logSessionTimeout($request);
        $this->terminateSession($request);

        return response()->json([
            'message' => 'セッションがタイムアウトしました。再度ログインしてください',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * セッションタイムアウトをログに記録
     */
    private function logSessionTimeout(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        /** @var string $staffId */
        $staffId = $user->getAuthIdentifier();
        SecurityLogger::sessionTimeout(
            staffId: $staffId,
            sessionId: $request->session()->getId(),
            timeoutType: 'absolute'
        );
    }

    /**
     * セッションを終了
     */
    private function terminateSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * セッション作成日時を設定
     *
     * ログイン成功時に呼び出される。
     */
    public static function setSessionCreatedAt(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_CREATED_AT_KEY, time());
        }
    }

    /**
     * セッション作成日時のキーを取得
     */
    public static function getSessionCreatedAtKey(): string
    {
        return self::SESSION_CREATED_AT_KEY;
    }
}
