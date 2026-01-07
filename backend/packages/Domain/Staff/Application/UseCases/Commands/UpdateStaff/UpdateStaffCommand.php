<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\UpdateStaff;

use Packages\Domain\Staff\Application\DTO\StaffAccount\UpdateStaffInput;

/**
 * 職員更新コマンド
 *
 * 職員更新ユースケースへの入力を表すコマンドオブジェクト。
 *
 * @feature EPIC-004-staff-account-edit
 */
final readonly class UpdateStaffCommand
{
    /**
     * コンストラクタ
     *
     * @param  string  $staffId  更新対象の職員ID
     * @param  UpdateStaffInput  $input  職員更新の入力データ
     * @param  string  $operatorId  操作を行う管理者のID
     */
    public function __construct(
        public string $staffId,
        public UpdateStaffInput $input,
        public string $operatorId,
    ) {}
}
