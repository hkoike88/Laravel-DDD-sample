<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Exceptions;

use DomainException;

/**
 * 不正な状態遷移が試みられた場合にスローされる例外
 *
 * BookStatus の状態遷移ルールに違反する操作が行われた場合にスローされる。
 */
final class InvalidBookStatusTransitionException extends DomainException
{
    /**
     * 遷移元の状態
     */
    private string $from;

    /**
     * 遷移先の状態
     */
    private string $to;

    /**
     * 試みられた操作名
     */
    private string $action;

    /**
     * コンストラクタ
     *
     * @param  string  $from  遷移元の状態
     * @param  string  $to  遷移先の状態
     * @param  string  $action  試みられた操作名
     */
    public function __construct(string $from, string $to, string $action)
    {
        $this->from = $from;
        $this->to = $to;
        $this->action = $action;

        $message = "{$from} 状態から {$to} への遷移は許可されていません（操作: {$action}）";

        parent::__construct($message);
    }

    /**
     * 遷移元の状態を取得
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * 遷移先の状態を取得
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * 試みられた操作名を取得
     */
    public function getAction(): string
    {
        return $this->action;
    }
}
