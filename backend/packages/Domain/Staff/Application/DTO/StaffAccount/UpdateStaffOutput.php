<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員更新レスポンスの出力データ
 *
 * @feature EPIC-004-staff-account-edit
 */
final readonly class UpdateStaffOutput
{
    /**
     * コンストラクタ
     *
     * @param  string  $id  職員ID（ULID）
     * @param  string  $name  職員名
     * @param  string  $email  メールアドレス
     * @param  string  $role  権限
     * @param  string  $updatedAt  更新日時（ISO 8601）
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $updatedAt,
    ) {}

    /**
     * 配列に変換（JSON レスポンス用）
     *
     * @return array{
     *   message: string,
     *   staff: array{id: string, name: string, email: string, role: string, updatedAt: string}
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => '職員情報を更新しました',
            'staff' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'updatedAt' => $this->updatedAt,
            ],
        ];
    }
}
