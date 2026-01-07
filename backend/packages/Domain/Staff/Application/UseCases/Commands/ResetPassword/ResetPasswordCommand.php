<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\ResetPassword;

/**
 * パスワードリセットコマンド
 *
 * 職員のパスワードをリセットするためのコマンドオブジェクト。
 *
 * @feature EPIC-004-staff-account-edit
 */
class ResetPasswordCommand
{
    /**
     * コンストラクタ
     *
     * @param  string  $staffId  対象職員ID
     * @param  string  $operatorId  操作者ID
     */
    public function __construct(
        public readonly string $staffId,
        public readonly string $operatorId,
    ) {}
}
