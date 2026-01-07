<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Auth;

use Packages\Domain\Staff\Application\DTO\Auth\StaffResponse;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * 認証済み職員情報取得ユースケース
 *
 * 認証済み職員の情報を取得する。
 */
final readonly class GetCurrentUserUseCase
{
    /**
     * コンストラクタ
     *
     * @param  StaffRepositoryInterface  $staffRepository  職員リポジトリ
     */
    public function __construct(
        private StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * 職員情報を取得
     *
     * @param  string  $staffIdValue  職員ID値
     * @return StaffResponse 職員レスポンス
     *
     * @throws \Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException 職員が見つからない場合
     */
    public function execute(string $staffIdValue): StaffResponse
    {
        $staffId = StaffId::fromString($staffIdValue);
        $staff = $this->staffRepository->find($staffId);

        return StaffResponse::fromEntity($staff);
    }
}
