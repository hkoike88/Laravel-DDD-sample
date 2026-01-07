<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 自己権限変更例外
 *
 * 管理者が自分自身の権限を変更しようとした場合にスローされる例外。
 *
 * @feature EPIC-004-staff-account-edit
 */
class SelfRoleChangeException extends StaffDomainException
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct(
            '自分自身の権限は変更できません'
        );
    }
}
