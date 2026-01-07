<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Queries\GetStaffDetail;

/**
 * 職員詳細取得クエリ
 *
 * 職員詳細取得ユースケースへの入力を表すクエリオブジェクト。
 *
 * @feature EPIC-004-staff-account-edit
 */
final readonly class GetStaffDetailQuery
{
    /**
     * コンストラクタ
     *
     * @param  string  $staffId  取得対象の職員ID
     * @param  string  $operatorId  操作を行う管理者のID
     */
    public function __construct(
        public string $staffId,
        public string $operatorId,
    ) {}
}
