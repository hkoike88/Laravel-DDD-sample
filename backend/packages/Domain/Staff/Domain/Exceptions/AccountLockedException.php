<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * アカウントロック例外
 *
 * アカウントがロックされている場合にスローされる例外。
 * ロック解除までの残り時間を含む。
 */
class AccountLockedException extends StaffDomainException
{
    /**
     * コンストラクタ
     *
     * @param  int  $retryAfterSeconds  リトライ可能までの秒数
     */
    public function __construct(
        private readonly int $retryAfterSeconds
    ) {
        parent::__construct('アカウントがロックされています');
    }

    /**
     * リトライ可能までの秒数を取得
     */
    public function retryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
