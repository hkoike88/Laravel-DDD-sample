<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員詳細レスポンスの出力データ
 *
 * @feature EPIC-004-staff-account-edit
 */
final readonly class StaffDetailOutput
{
    /**
     * コンストラクタ
     *
     * @param  string  $id  職員ID（ULID）
     * @param  string  $name  職員名
     * @param  string  $email  メールアドレス
     * @param  string  $role  権限（'staff' | 'admin'）
     * @param  bool  $isCurrentUser  ログイン中のユーザーかどうか
     * @param  string  $updatedAt  更新日時（ISO 8601）
     * @param  string  $createdAt  作成日時（ISO 8601）
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public bool $isCurrentUser,
        public string $updatedAt,
        public string $createdAt,
    ) {}

    /**
     * 配列に変換（JSON レスポンス用）
     *
     * @return array{
     *   data: array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     role: string,
     *     isCurrentUser: bool,
     *     updatedAt: string,
     *     createdAt: string
     *   }
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'isCurrentUser' => $this->isCurrentUser,
                'updatedAt' => $this->updatedAt,
                'createdAt' => $this->createdAt,
            ],
        ];
    }
}
