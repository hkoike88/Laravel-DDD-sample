<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\Repositories;

use DateTimeImmutable;
use Packages\Domain\Staff\Domain\Model\PasswordHistory;
use Packages\Domain\Staff\Domain\Repositories\PasswordHistoryRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Infrastructure\EloquentModels\PasswordHistoryRecord;

/**
 * パスワード履歴リポジトリ実装
 *
 * Eloquent を使用してパスワード履歴の永続化を行う。
 *
 * @feature 001-security-preparation
 */
final class PasswordHistoryRepository implements PasswordHistoryRepositoryInterface
{
    /**
     * 職員の最新N世代のパスワード履歴を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @param  int  $limit  取得する世代数（デフォルト: 5）
     * @return array<PasswordHistory> パスワード履歴の配列（新しい順）
     */
    public function findRecentByStaffId(StaffId $staffId, int $limit = 5): array
    {
        $records = PasswordHistoryRecord::query()
            ->where('staff_id', $staffId->value())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $records->map(fn (PasswordHistoryRecord $record) => $this->toEntity($record))->all();
    }

    /**
     * パスワード履歴を保存
     *
     * @param  PasswordHistory  $passwordHistory  パスワード履歴
     */
    public function save(PasswordHistory $passwordHistory): void
    {
        PasswordHistoryRecord::create([
            'id' => $passwordHistory->id(),
            'staff_id' => $passwordHistory->staffId()->value(),
            'password_hash' => $passwordHistory->passwordHash(),
            'created_at' => $passwordHistory->createdAt(),
        ]);
    }

    /**
     * 職員のパスワード履歴数を取得
     *
     * @param  StaffId  $staffId  職員ID
     * @return int 履歴数
     */
    public function countByStaffId(StaffId $staffId): int
    {
        return PasswordHistoryRecord::query()
            ->where('staff_id', $staffId->value())
            ->count();
    }

    /**
     * 職員の古いパスワード履歴を削除
     *
     * 最新N世代以外のパスワード履歴を削除する。
     *
     * @param  StaffId  $staffId  職員ID
     * @param  int  $keepCount  保持する世代数（デフォルト: 5）
     * @return int 削除した件数
     */
    public function deleteOldByStaffId(StaffId $staffId, int $keepCount = 5): int
    {
        // 保持するIDを取得
        $keepIds = PasswordHistoryRecord::query()
            ->where('staff_id', $staffId->value())
            ->orderBy('created_at', 'desc')
            ->limit($keepCount)
            ->pluck('id')
            ->toArray();

        if (empty($keepIds)) {
            return 0;
        }

        // 保持対象以外を削除
        /** @var int $deletedCount */
        $deletedCount = PasswordHistoryRecord::query()
            ->where('staff_id', $staffId->value())
            ->whereNotIn('id', $keepIds)
            ->delete();

        return $deletedCount;
    }

    /**
     * Eloquent モデルからドメインエンティティに変換
     *
     * @param  PasswordHistoryRecord  $record  Eloquent モデル
     * @return PasswordHistory ドメインエンティティ
     */
    private function toEntity(PasswordHistoryRecord $record): PasswordHistory
    {
        return PasswordHistory::reconstruct(
            id: $record->id,
            staffId: StaffId::fromString($record->staff_id),
            passwordHash: $record->password_hash,
            createdAt: new DateTimeImmutable($record->created_at->toDateTimeString()),
        );
    }
}
