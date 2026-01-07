<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

use Packages\Domain\Staff\Domain\Model\Staff;

/**
 * 職員一覧の1件分の出力データ
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class StaffListOutput
{
    /**
     * コンストラクタ
     *
     * @param  string  $id  職員ID（ULID）
     * @param  string  $name  職員名
     * @param  string  $email  メールアドレス
     * @param  string  $role  権限
     * @param  string  $createdAt  作成日時（ISO 8601）
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $createdAt,
    ) {}

    /**
     * Staff エンティティから DTO を生成
     *
     * @param  Staff  $staff  職員エンティティ
     * @param  string  $createdAt  作成日時（ISO 8601）
     */
    public static function fromEntity(Staff $staff, string $createdAt): self
    {
        return new self(
            id: $staff->id()->value(),
            name: $staff->name()->value(),
            email: $staff->email()->value(),
            role: $staff->isAdmin() ? 'admin' : 'staff',
            createdAt: $createdAt,
        );
    }

    /**
     * 配列に変換
     *
     * @return array{id: string, name: string, email: string, role: string, createdAt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'createdAt' => $this->createdAt,
        ];
    }
}
