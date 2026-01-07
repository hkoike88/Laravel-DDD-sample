<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員更新リクエストの入力データ
 *
 * @feature EPIC-004-staff-account-edit
 */
final readonly class UpdateStaffInput
{
    /**
     * コンストラクタ
     *
     * @param  string  $name  職員名（100文字以内）
     * @param  string  $email  メールアドレス（255文字以内）
     * @param  string  $role  権限（'staff' | 'admin'）
     * @param  string  $updatedAt  更新日時（ISO 8601形式、楽観的ロック用）
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
        public string $updatedAt,
    ) {}

    /**
     * 管理者権限かどうか
     *
     * @return bool 管理者の場合 true
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
