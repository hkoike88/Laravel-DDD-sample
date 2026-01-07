<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Session;

use Packages\Domain\Staff\Domain\Services\SessionManagerService;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * 他セッション一括終了アクション
 *
 * 現在のセッション以外の全セッションを終了する。
 * 「全デバイスからログアウト」機能に使用。
 *
 * @feature 001-security-preparation
 */
final class TerminateOtherSessionsAction
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
     * 他セッションを一括終了
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $currentSessionId  維持するセッションID
     * @return int 終了したセッション数
     */
    public function execute(StaffId $staffId, string $currentSessionId): int
    {
        return $this->sessionManager->terminateOtherSessions($staffId, $currentSessionId);
    }
}
