<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Services;

use Packages\Domain\Staff\Domain\Model\PasswordHistory;
use Packages\Domain\Staff\Domain\Repositories\PasswordHistoryRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * パスワード履歴サービス
 *
 * パスワード履歴のチェックと追加を行うドメインサービス。
 *
 * @feature 001-security-preparation
 */
final class PasswordHistoryService
{
    /**
     * 保持する履歴世代数
     */
    private const HISTORY_COUNT = 5;

    /**
     * コンストラクタ
     *
     * @param  PasswordHistoryRepositoryInterface  $repository  パスワード履歴リポジトリ
     */
    public function __construct(
        private readonly PasswordHistoryRepositoryInterface $repository,
    ) {}

    /**
     * パスワードが過去に使用されたものかチェック
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $plainPassword  平文パスワード
     * @return bool 再利用の場合 true
     */
    public function isPasswordReused(StaffId $staffId, string $plainPassword): bool
    {
        $histories = $this->repository->findRecentByStaffId($staffId, self::HISTORY_COUNT);

        foreach ($histories as $history) {
            if ($history->matches($plainPassword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * パスワード履歴に追加
     *
     * 新しいパスワードを履歴に追加し、古い履歴を削除する。
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $hashedPassword  ハッシュ化済みパスワード
     */
    public function addToHistory(StaffId $staffId, string $hashedPassword): void
    {
        // 新しい履歴を作成
        $passwordHistory = PasswordHistory::create($staffId, $hashedPassword);

        // 履歴を保存
        $this->repository->save($passwordHistory);

        // 古い履歴を削除（5世代を超える分）
        $this->repository->deleteOldByStaffId($staffId, self::HISTORY_COUNT);
    }
}
