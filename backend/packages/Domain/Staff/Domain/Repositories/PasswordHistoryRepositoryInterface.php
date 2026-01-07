<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Repositories;

use Packages\Domain\Staff\Domain\Model\PasswordHistory;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * パスワード履歴リポジトリインターフェース
 *
 * パスワード履歴の永続化操作を定義する。
 *
 * @feature 001-security-preparation
 */
interface PasswordHistoryRepositoryInterface
{
    /**
     * 職員の最新N世代のパスワード履歴を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @param  int  $limit  取得する世代数（デフォルト: 5）
     * @return array<PasswordHistory> パスワード履歴の配列（新しい順）
     */
    public function findRecentByStaffId(StaffId $staffId, int $limit = 5): array;

    /**
     * パスワード履歴を保存
     *
     * @param  PasswordHistory  $passwordHistory  パスワード履歴
     */
    public function save(PasswordHistory $passwordHistory): void;

    /**
     * 職員のパスワード履歴数を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @return int 履歴数
     */
    public function countByStaffId(StaffId $staffId): int;

    /**
     * 職員の古いパスワード履歴を削除
     *
     * 最新N世代以外のパスワード履歴を削除する。
     *
     * @param  StaffId  $staffId  職員ID
     * @param  int  $keepCount  保持する世代数（デフォルト: 5）
     * @return int 削除した件数
     */
    public function deleteOldByStaffId(StaffId $staffId, int $keepCount = 5): int;
}
