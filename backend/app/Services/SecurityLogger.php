<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * セキュリティロガーサービス
 *
 * セキュリティ関連イベントを専用ログチャンネルに記録する。
 * 全イベントは JSON 形式で記録され、監査とインシデント分析に使用される。
 *
 * @feature 001-security-preparation
 */
final class SecurityLogger
{
    /**
     * ログチャンネル名
     */
    private const CHANNEL = 'security';

    /**
     * イベントタイプ定数
     */
    public const EVENT_LOGIN_SUCCESS = 'login_success';

    public const EVENT_LOGIN_FAILURE = 'login_failure';

    public const EVENT_ACCOUNT_LOCKED = 'account_locked';

    public const EVENT_PASSWORD_CHANGED = 'password_changed';

    public const EVENT_SESSION_TIMEOUT = 'session_timeout';

    public const EVENT_SESSION_TERMINATED = 'session_terminated';

    public const EVENT_SESSION_TERMINATED_OTHERS = 'session_terminated_others';

    /**
     * ログイン成功イベントを記録
     *
     * @param  string  $staffId  職員ID
     * @param  string  $email  メールアドレス
     * @param  string  $ipAddress  IPアドレス
     * @param  string|null  $userAgent  ユーザーエージェント
     */
    public static function loginSuccess(
        string $staffId,
        string $email,
        string $ipAddress,
        ?string $userAgent = null
    ): void {
        self::log(self::EVENT_LOGIN_SUCCESS, [
            'staff_id' => $staffId,
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * ログイン失敗イベントを記録
     *
     * @param  string  $email  試行されたメールアドレス
     * @param  string  $ipAddress  IPアドレス
     * @param  string  $reason  失敗理由
     * @param  string|null  $userAgent  ユーザーエージェント
     */
    public static function loginFailure(
        string $email,
        string $ipAddress,
        string $reason,
        ?string $userAgent = null
    ): void {
        self::log(self::EVENT_LOGIN_FAILURE, [
            'email' => $email,
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'user_agent' => $userAgent,
        ], 'warning');
    }

    /**
     * アカウントロックイベントを記録
     *
     * @param  string  $staffId  職員ID
     * @param  string  $email  メールアドレス
     * @param  string  $ipAddress  IPアドレス
     * @param  int  $failedAttempts  失敗試行回数
     */
    public static function accountLocked(
        string $staffId,
        string $email,
        string $ipAddress,
        int $failedAttempts
    ): void {
        self::log(self::EVENT_ACCOUNT_LOCKED, [
            'staff_id' => $staffId,
            'email' => $email,
            'ip_address' => $ipAddress,
            'failed_attempts' => $failedAttempts,
        ], 'warning');
    }

    /**
     * パスワード変更イベントを記録
     *
     * @param  string  $staffId  職員ID
     * @param  string  $ipAddress  IPアドレス
     */
    public static function passwordChanged(
        string $staffId,
        string $ipAddress
    ): void {
        self::log(self::EVENT_PASSWORD_CHANGED, [
            'staff_id' => $staffId,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * セッションタイムアウトイベントを記録
     *
     * @param  string  $staffId  職員ID
     * @param  string  $sessionId  セッションID
     * @param  string  $timeoutType  タイムアウト種別（idle/absolute）
     */
    public static function sessionTimeout(
        string $staffId,
        string $sessionId,
        string $timeoutType = 'absolute'
    ): void {
        self::log(self::EVENT_SESSION_TIMEOUT, [
            'staff_id' => $staffId,
            'session_id' => substr($sessionId, 0, 8).'...', // セッションIDの一部のみ記録
            'timeout_type' => $timeoutType,
        ]);
    }

    /**
     * セッション終了イベントを記録
     *
     * @param  string  $staffId  職員ID
     * @param  string  $sessionId  終了されたセッションID
     * @param  string  $terminatedBy  終了者（self/admin）
     */
    public static function sessionTerminated(
        string $staffId,
        string $sessionId,
        string $terminatedBy = 'self'
    ): void {
        self::log(self::EVENT_SESSION_TERMINATED, [
            'staff_id' => $staffId,
            'session_id' => substr($sessionId, 0, 8).'...',
            'terminated_by' => $terminatedBy,
        ]);
    }

    /**
     * 他セッション一括終了イベントを記録
     *
     * @param  string  $staffId  職員ID
     * @param  int  $count  終了したセッション数
     */
    public static function sessionTerminatedOthers(
        string $staffId,
        int $count
    ): void {
        self::log(self::EVENT_SESSION_TERMINATED_OTHERS, [
            'staff_id' => $staffId,
            'terminated_count' => $count,
        ]);
    }

    /**
     * セキュリティログを記録
     *
     * @param  string  $event  イベントタイプ
     * @param  array<string, mixed>  $context  コンテキスト情報
     * @param  string  $level  ログレベル
     */
    private static function log(string $event, array $context, string $level = 'info'): void
    {
        $context['event'] = $event;
        $context['timestamp'] = now()->toIso8601String();

        Log::channel(self::CHANNEL)->$level(
            "Security Event: {$event}",
            $context
        );
    }
}
