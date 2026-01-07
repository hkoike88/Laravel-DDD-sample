<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Model;

use DateTimeImmutable;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Symfony\Component\Uid\Ulid;

/**
 * パスワード履歴エンティティ
 *
 * 職員のパスワード履歴を管理し、過去5世代のパスワード再利用を禁止するために使用。
 * 作成のみ可能で、更新は行わない。
 *
 * @feature 001-security-preparation
 */
final readonly class PasswordHistory
{
    /**
     * コンストラクタ
     *
     * @param  string  $id  パスワード履歴ID（ULID）
     * @param  StaffId  $staffId  職員ID
     * @param  string  $passwordHash  ハッシュ化済みパスワード
     * @param  DateTimeImmutable  $createdAt  作成日時
     */
    private function __construct(
        private string $id,
        private StaffId $staffId,
        private string $passwordHash,
        private DateTimeImmutable $createdAt,
    ) {}

    /**
     * 新規パスワード履歴を作成
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $passwordHash  ハッシュ化済みパスワード
     */
    public static function create(
        StaffId $staffId,
        string $passwordHash,
    ): self {
        $ulid = new Ulid;

        return new self(
            id: $ulid->toBase32(),
            staffId: $staffId,
            passwordHash: $passwordHash,
            createdAt: new DateTimeImmutable,
        );
    }

    /**
     * 永続化データからパスワード履歴を復元
     *
     * @param  string  $id  パスワード履歴ID
     * @param  StaffId  $staffId  職員ID
     * @param  string  $passwordHash  ハッシュ化済みパスワード
     * @param  DateTimeImmutable  $createdAt  作成日時
     */
    public static function reconstruct(
        string $id,
        StaffId $staffId,
        string $passwordHash,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            staffId: $staffId,
            passwordHash: $passwordHash,
            createdAt: $createdAt,
        );
    }

    /**
     * パスワード履歴IDを取得
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * 職員IDを取得
     */
    public function staffId(): StaffId
    {
        return $this->staffId;
    }

    /**
     * ハッシュ化済みパスワードを取得
     */
    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * 作成日時を取得
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * 指定されたパスワードが履歴と一致するかチェック
     *
     * @param  string  $plainPassword  平文パスワード
     * @return bool 一致する場合 true
     */
    public function matches(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }
}
