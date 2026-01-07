<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Model;

use DateTimeImmutable;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;

/**
 * 職員エンティティ
 *
 * 図書館システムの職員を表すエンティティ（集約ルート）。
 * 認証情報とアカウント状態を管理する。
 */
final class Staff
{
    /**
     * コンストラクタ
     *
     * @param  StaffId  $id  職員ID
     * @param  Email  $email  メールアドレス
     * @param  Password  $password  パスワード
     * @param  StaffName  $name  職員名
     * @param  bool  $isAdmin  管理者フラグ
     * @param  bool  $isLocked  ロック状態
     * @param  int  $failedLoginAttempts  連続ログイン失敗回数
     * @param  DateTimeImmutable|null  $lockedAt  ロック日時
     */
    private function __construct(
        private readonly StaffId $id,
        private readonly Email $email,
        private Password $password,
        private readonly StaffName $name,
        private readonly bool $isAdmin,
        private bool $isLocked,
        private int $failedLoginAttempts,
        private ?DateTimeImmutable $lockedAt,
    ) {}

    /**
     * 新規職員を作成
     *
     * @param  StaffId  $id  職員ID
     * @param  Email  $email  メールアドレス
     * @param  Password  $password  パスワード
     * @param  StaffName  $name  職員名
     * @param  bool  $isAdmin  管理者フラグ（デフォルト: false）
     */
    public static function create(
        StaffId $id,
        Email $email,
        Password $password,
        StaffName $name,
        bool $isAdmin = false,
    ): self {
        return new self(
            id: $id,
            email: $email,
            password: $password,
            name: $name,
            isAdmin: $isAdmin,
            isLocked: false,
            failedLoginAttempts: 0,
            lockedAt: null,
        );
    }

    /**
     * 永続化データから職員を復元
     *
     * @param  StaffId  $id  職員ID
     * @param  Email  $email  メールアドレス
     * @param  Password  $password  パスワード
     * @param  StaffName  $name  職員名
     * @param  bool  $isAdmin  管理者フラグ
     * @param  bool  $isLocked  ロック状態
     * @param  int  $failedLoginAttempts  連続ログイン失敗回数
     * @param  DateTimeImmutable|null  $lockedAt  ロック日時
     */
    public static function reconstruct(
        StaffId $id,
        Email $email,
        Password $password,
        StaffName $name,
        bool $isAdmin,
        bool $isLocked,
        int $failedLoginAttempts,
        ?DateTimeImmutable $lockedAt,
    ): self {
        return new self(
            id: $id,
            email: $email,
            password: $password,
            name: $name,
            isAdmin: $isAdmin,
            isLocked: $isLocked,
            failedLoginAttempts: $failedLoginAttempts,
            lockedAt: $lockedAt,
        );
    }

    /**
     * 職員IDを取得
     */
    public function id(): StaffId
    {
        return $this->id;
    }

    /**
     * メールアドレスを取得
     */
    public function email(): Email
    {
        return $this->email;
    }

    /**
     * パスワードを取得
     */
    public function password(): Password
    {
        return $this->password;
    }

    /**
     * 職員名を取得
     */
    public function name(): StaffName
    {
        return $this->name;
    }

    /**
     * 管理者かどうかを取得
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * ロック状態を取得
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    /**
     * 連続ログイン失敗回数を取得
     */
    public function failedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    /**
     * ロック日時を取得
     */
    public function lockedAt(): ?DateTimeImmutable
    {
        return $this->lockedAt;
    }

    /**
     * パスワードを検証
     *
     * @param  string  $plainText  平文パスワード
     * @return bool 一致する場合 true
     */
    public function verifyPassword(string $plainText): bool
    {
        return $this->password->verify($plainText);
    }

    /**
     * アカウントをロック
     *
     * ロック状態を true に設定し、ロック日時を記録する。
     */
    public function lock(): void
    {
        $this->isLocked = true;
        $this->lockedAt = new DateTimeImmutable;
    }

    /**
     * アカウントをアンロック
     *
     * ロック状態を false に設定し、ロック日時と失敗回数をリセットする。
     */
    public function unlock(): void
    {
        $this->isLocked = false;
        $this->lockedAt = null;
        $this->failedLoginAttempts = 0;
    }

    /**
     * ログイン失敗回数をインクリメント
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->failedLoginAttempts++;
    }

    /**
     * ログイン失敗回数をリセット
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->failedLoginAttempts = 0;
    }
}
