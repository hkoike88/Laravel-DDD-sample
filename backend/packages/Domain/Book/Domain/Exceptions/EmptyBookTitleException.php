<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Exceptions;

use DomainException;

/**
 * 蔵書タイトルが空白の場合にスローされる例外
 *
 * タイトルは蔵書の必須属性であり、空文字または空白のみの値は許可されない。
 */
final class EmptyBookTitleException extends DomainException
{
    /**
     * 例外メッセージ
     */
    private const MESSAGE = '蔵書タイトルは必須です';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
