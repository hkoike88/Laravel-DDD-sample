<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\Auth;

use Packages\Domain\Staff\Domain\Model\Staff;

/**
 * 職員レスポンス DTO
 *
 * 認証済み職員情報のレスポンスを表す。
 */
final readonly class StaffResponse
{
    /**
     * コンストラクタ
     *
     * @param  string  $id  職員ID
     * @param  string  $name  職員名
     * @param  string  $email  メールアドレス
     * @param  bool  $isAdmin  管理者フラグ
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $isAdmin,
    ) {}

    /**
     * Staff エンティティからレスポンスを生成
     *
     * @param  Staff  $staff  職員エンティティ
     */
    public static function fromEntity(Staff $staff): self
    {
        return new self(
            id: $staff->id()->value(),
            name: $staff->name()->value(),
            email: $staff->email()->value(),
            isAdmin: $staff->isAdmin(),
        );
    }

    /**
     * 配列に変換
     *
     * @return array{id: string, name: string, email: string, is_admin: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->isAdmin,
        ];
    }
}
