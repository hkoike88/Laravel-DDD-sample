<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * 職員作成リクエストの入力データ
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class CreateStaffInput
{
    /**
     * コンストラクタ
     *
     * @param  string  $name  職員名（50文字以内）
     * @param  string  $email  メールアドレス（255文字以内、一意）
     * @param  string  $role  権限（'staff' | 'admin'）
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
    ) {}
}
