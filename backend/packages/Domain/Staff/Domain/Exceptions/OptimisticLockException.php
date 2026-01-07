<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 楽観的ロック例外
 *
 * 職員情報の更新時に、他のユーザーによって既に更新されていた場合にスローされる例外。
 *
 * @feature EPIC-004-staff-account-edit
 */
class OptimisticLockException extends StaffDomainException
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct(
            '他のユーザーによって更新されています。最新の情報を確認してください'
        );
    }
}
