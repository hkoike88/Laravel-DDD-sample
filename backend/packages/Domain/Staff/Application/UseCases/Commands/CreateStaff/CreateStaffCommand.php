<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\CreateStaff;

use Packages\Domain\Staff\Application\DTO\StaffAccount\CreateStaffInput;

/**
 * 職員作成コマンド
 *
 * 職員作成ユースケースへの入力を表すコマンドオブジェクト。
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class CreateStaffCommand
{
    /**
     * コンストラクタ
     *
     * @param  CreateStaffInput  $input  職員作成の入力データ
     * @param  string  $operatorId  操作を行う管理者のID
     */
    public function __construct(
        public CreateStaffInput $input,
        public string $operatorId,
    ) {}
}
