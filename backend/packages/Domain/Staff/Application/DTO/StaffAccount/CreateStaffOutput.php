<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員作成レスポンスの出力データ
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class CreateStaffOutput
{
    /**
     * コンストラクタ
     *
     * @param  string  $id  職員ID（ULID）
     * @param  string  $name  職員名
     * @param  string  $email  メールアドレス
     * @param  string  $role  権限
     * @param  string  $temporaryPassword  初期パスワード（平文、この場面でのみ使用）
     * @param  string  $createdAt  作成日時（ISO 8601）
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $temporaryPassword,
        public string $createdAt,
    ) {}

    /**
     * 配列に変換（JSON レスポンス用）
     *
     * @return array{
     *   message: string,
     *   staff: array{id: string, name: string, email: string, role: string, createdAt: string},
     *   temporaryPassword: string
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => '職員アカウントを作成しました',
            'staff' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'createdAt' => $this->createdAt,
            ],
            'temporaryPassword' => $this->temporaryPassword,
        ];
    }
}
