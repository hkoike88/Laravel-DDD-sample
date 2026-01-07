<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 最後の管理者保護例外
 *
 * システム内で唯一の管理者の権限を一般職員に変更しようとした場合にスローされる例外。
 *
 * @feature EPIC-004-staff-account-edit
 */
class LastAdminProtectionException extends StaffDomainException
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct(
            '最後の管理者アカウントの権限は変更できません'
        );
    }
}
