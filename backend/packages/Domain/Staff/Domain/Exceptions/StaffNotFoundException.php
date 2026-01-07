<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * 職員未検出例外
 *
 * 指定された職員が見つからない場合にスローされる例外。
 */
class StaffNotFoundException extends StaffDomainException
{
    /**
     * コンストラクタ
     *
     * @param  StaffId  $staffId  検索された職員ID
     */
    public function __construct(StaffId $staffId)
    {
        parent::__construct(
            sprintf('職員が見つかりません: %s', $staffId->value())
        );
    }
}
