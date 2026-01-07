<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Queries\GetStaffDetail;

use Packages\Domain\Staff\Application\DTO\StaffAccount\StaffDetailOutput;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;

/**
 * 職員詳細取得ハンドラー
 *
 * 指定された職員の詳細情報を取得する。
 *
 * @feature EPIC-004-staff-account-edit
 */
class GetStaffDetailHandler
{
    /**
     * コンストラクタ
     *
     * @param  StaffRepositoryInterface  $staffRepository  職員リポジトリ
     */
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    /**
     * 職員詳細取得クエリを実行
     *
     * @param  GetStaffDetailQuery  $query  職員詳細取得クエリ
     * @return StaffDetailOutput 職員詳細情報
     *
     * @throws \Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException 職員が存在しない場合
     */
    public function handle(GetStaffDetailQuery $query): StaffDetailOutput
    {
        $staffId = StaffId::fromString($query->staffId);
        // 職員を取得（存在しない場合はStaffNotFoundExceptionがthrowされる）
        $staff = $this->staffRepository->find($staffId);

        // updated_at と created_at を取得するためにEloquentレコードも取得（上記でfindが成功しているので必ず存在する）
        $record = StaffRecord::find($query->staffId);
        assert($record instanceof StaffRecord);

        $isCurrentUser = $query->staffId === $query->operatorId;

        return new StaffDetailOutput(
            id: $staff->id()->value(),
            name: $staff->name()->value(),
            email: $staff->email()->value(),
            role: $staff->isAdmin() ? 'admin' : 'staff',
            isCurrentUser: $isCurrentUser,
            updatedAt: $record->updated_at->toIso8601String(),
            createdAt: $record->created_at->toIso8601String(),
        );
    }
}
