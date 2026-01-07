<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\Repositories;

use Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;

/**
 * 職員リポジトリ Eloquent 実装
 *
 * Eloquent を使用した職員リポジトリの具体実装。
 */
class EloquentStaffRepository implements StaffRepositoryInterface
{
    /**
     * ID で職員を取得
     *
     * @throws StaffNotFoundException 職員が存在しない場合
     */
    public function find(StaffId $id): Staff
    {
        $staff = $this->findOrNull($id);

        if ($staff === null) {
            throw new StaffNotFoundException($id);
        }

        return $staff;
    }

    /**
     * ID で職員を取得（存在しない場合は null）
     */
    public function findOrNull(StaffId $id): ?Staff
    {
        $record = StaffRecord::find($id->value());

        if ($record === null) {
            return null;
        }

        return $this->toEntity($record);
    }

    /**
     * メールアドレスで職員を検索
     */
    public function findByEmail(Email $email): ?Staff
    {
        $record = StaffRecord::where('email', $email->value())->first();

        if ($record === null) {
            return null;
        }

        return $this->toEntity($record);
    }

    /**
     * メールアドレスの存在確認
     */
    public function existsByEmail(Email $email): bool
    {
        return StaffRecord::where('email', $email->value())->exists();
    }

    /**
     * 職員を保存（新規作成または更新）
     */
    public function save(Staff $staff): void
    {
        StaffRecord::updateOrCreate(
            ['id' => $staff->id()->value()],
            [
                'email' => $staff->email()->value(),
                'password' => $staff->password()->hashedValue(),
                'name' => $staff->name()->value(),
                'is_admin' => $staff->isAdmin(),
                'is_locked' => $staff->isLocked(),
                'failed_login_attempts' => $staff->failedLoginAttempts(),
                'locked_at' => $staff->lockedAt(),
            ]
        );
    }

    /**
     * 職員を削除
     */
    public function delete(StaffId $id): void
    {
        StaffRecord::where('id', $id->value())->delete();
    }

    /**
     * ページネーション付きで全職員を取得
     *
     * @param  int  $page  ページ番号（1始まり）
     * @param  int  $perPage  1ページあたりの件数
     * @return array{
     *   data: Staff[],
     *   currentPage: int,
     *   lastPage: int,
     *   perPage: int,
     *   total: int,
     *   from: int|null,
     *   to: int|null
     * }
     *
     * @feature EPIC-003-staff-account-create
     */
    public function findAllPaginated(int $page = 1, int $perPage = 20): array
    {
        $paginator = StaffRecord::query()
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $perPage, page: $page);

        $data = [];
        foreach ($paginator->items() as $record) {
            $data[] = $this->toEntity($record);
        }

        return [
            'data' => $data,
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * 管理者の人数をカウント
     *
     * @return int 管理者数
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function countAdmins(): int
    {
        return StaffRecord::where('is_admin', true)->count();
    }

    /**
     * 指定した職員以外でメールアドレスが存在するか確認
     *
     * @param  Email  $email  確認するメールアドレス
     * @param  StaffId  $excludeId  除外する職員ID
     * @return bool 他の職員が同じメールアドレスを使用している場合 true
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function existsByEmailExcludingId(Email $email, StaffId $excludeId): bool
    {
        return StaffRecord::where('email', $email->value())
            ->where('id', '!=', $excludeId->value())
            ->exists();
    }

    /**
     * 管理者の人数をカウント（排他ロック付き）
     *
     * トランザクション内で使用し、最後の管理者保護などの
     * 競合状態を防ぐための排他ロック（SELECT ... FOR UPDATE）を取得する。
     *
     * @return int 管理者数
     *
     * @feature EPIC-004-staff-account-edit
     */
    public function countAdminsForUpdate(): int
    {
        return StaffRecord::where('is_admin', true)
            ->lockForUpdate()
            ->count();
    }

    /**
     * Eloquent レコードからエンティティに変換
     */
    private function toEntity(StaffRecord $record): Staff
    {
        return Staff::reconstruct(
            id: StaffId::fromString($record->id),
            email: Email::fromString($record->email),
            password: Password::fromHash($record->password),
            name: StaffName::fromString($record->name),
            isAdmin: $record->is_admin,
            isLocked: $record->is_locked,
            failedLoginAttempts: $record->failed_login_attempts,
            lockedAt: $record->locked_at?->toDateTimeImmutable(),
        );
    }
}
