<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

use Packages\Domain\Staff\Domain\ValueObjects\Email;

/**
 * メールアドレス重複例外
 *
 * 既に使用されているメールアドレスで職員を登録しようとした場合にスローされる例外。
 */
class DuplicateEmailException extends StaffDomainException
{
    /**
     * コンストラクタ
     *
     * @param  Email  $email  重複しているメールアドレス
     */
    public function __construct(Email $email)
    {
        parent::__construct(
            sprintf('このメールアドレスは既に使用されています: %s', $email->value())
        );
    }
}
