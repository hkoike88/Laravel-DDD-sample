<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;

/**
 * ログアウトユースケース
 *
 * 職員のログアウト処理を実行する。
 */
final readonly class LogoutUseCase
{
    /**
     * コンストラクタ
     *
     * @param  StatefulGuard  $guard  認証ガード
     */
    public function __construct(
        private StatefulGuard $guard
    ) {}

    /**
     * ログアウト処理を実行
     */
    public function execute(): void
    {
        $this->guard->logout();
    }
}
