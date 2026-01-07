<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Session;

use Packages\Domain\Staff\Domain\Services\SessionManagerService;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * セッション終了アクション
 *
 * 指定されたセッションを終了する。
 * 所有者確認を行い、他人のセッションは終了できない。
 *
 * @feature 001-security-preparation
 */
final class TerminateSessionAction
{
    /**
     * コンストラクタ
     *
     * @param  SessionManagerService  $sessionManager  セッション管理サービス
     */
    public function __construct(
        private readonly SessionManagerService $sessionManager,
    ) {}

    /**
     * セッションを終了
     *
     * @param  StaffId  $staffId  職員ID（所有者確認用）
     * @param  string  $sessionId  終了するセッションID
     * @return bool 終了成功の場合 true
     */
    public function execute(StaffId $staffId, string $sessionId): bool
    {
        return $this->sessionManager->terminateSession($staffId, $sessionId);
    }
}
