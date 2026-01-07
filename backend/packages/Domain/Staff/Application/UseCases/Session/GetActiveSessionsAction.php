<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Session;

use Packages\Domain\Staff\Domain\Services\SessionManagerService;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * アクティブセッション取得アクション
 *
 * 認証済み職員のアクティブなセッション一覧を取得する。
 *
 * @feature 001-security-preparation
 */
final class GetActiveSessionsAction
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
     * アクティブセッション一覧を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $currentSessionId  現在のセッションID
     * @return array<int, array{id: string, ip_address: string|null, user_agent: string|null, last_activity: int, is_current: bool}> セッション一覧
     */
    public function execute(StaffId $staffId, string $currentSessionId): array
    {
        $sessions = $this->sessionManager->getActiveSessions($staffId);

        $result = [];
        foreach ($sessions as $session) {
            /** @var \stdClass $session */
            $result[] = [
                'id' => (string) $session->id,
                'ip_address' => property_exists($session, 'ip_address') ? $session->ip_address : null,
                'user_agent' => property_exists($session, 'user_agent') ? $session->user_agent : null,
                'last_activity' => (int) $session->last_activity,
                'is_current' => $session->id === $currentSessionId,
            ];
        }

        return $result;
    }
}
