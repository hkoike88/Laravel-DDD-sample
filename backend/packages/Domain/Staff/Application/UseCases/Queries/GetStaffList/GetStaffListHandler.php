<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Queries\GetStaffList;

use DateTimeImmutable;
use Packages\Domain\Staff\Application\DTO\StaffAccount\PaginationLinks;
use Packages\Domain\Staff\Application\DTO\StaffAccount\StaffListOutput;
use Packages\Domain\Staff\Application\DTO\StaffAccount\StaffListPaginatedOutput;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;

/**
 * 職員一覧取得ハンドラー
 *
 * ページネーション付きで職員一覧を取得する。
 *
 * @feature EPIC-003-staff-account-create
 */
class GetStaffListHandler
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
     * 職員一覧取得クエリを実行
     *
     * @param  GetStaffListQuery  $query  職員一覧取得クエリ
     * @return StaffListPaginatedOutput ページネーション付き職員一覧
     */
    public function handle(GetStaffListQuery $query): StaffListPaginatedOutput
    {
        // リポジトリからページネーション付きデータを取得
        $result = $this->staffRepository->findAllPaginated($query->page, $query->perPage);

        // 作成日時を取得するために直接クエリ（リポジトリからは取得できないため）
        $staffIds = array_map(fn (Staff $staff) => $staff->id()->value(), $result['data']);
        $createdAtMap = $this->getCreatedAtMap($staffIds);

        // DTO に変換
        $items = [];
        foreach ($result['data'] as $staff) {
            $createdAt = $createdAtMap[$staff->id()->value()] ?? new DateTimeImmutable;
            $items[] = StaffListOutput::fromEntity($staff, $createdAt->format(DateTimeImmutable::ATOM));
        }

        // ページネーションリンクを生成
        $links = $this->buildPaginationLinks(
            baseUrl: $query->baseUrl,
            currentPage: $result['currentPage'],
            lastPage: $result['lastPage'],
        );

        return new StaffListPaginatedOutput(
            data: $items,
            currentPage: $result['currentPage'],
            lastPage: $result['lastPage'],
            perPage: $result['perPage'],
            total: $result['total'],
            from: $result['from'],
            to: $result['to'],
            links: $links,
        );
    }

    /**
     * 職員IDごとの作成日時マップを取得
     *
     * @param  string[]  $staffIds  職員IDリスト
     * @return array<string, DateTimeImmutable> ID => 作成日時のマップ
     */
    private function getCreatedAtMap(array $staffIds): array
    {
        if (empty($staffIds)) {
            return [];
        }

        $records = StaffRecord::whereIn('id', $staffIds)->get(['id', 'created_at']);

        $map = [];
        foreach ($records as $record) {
            $map[$record->id] = $record->created_at->toDateTimeImmutable();
        }

        return $map;
    }

    /**
     * ページネーションリンクを生成
     *
     * @param  string  $baseUrl  ベースURL
     * @param  int  $currentPage  現在のページ
     * @param  int  $lastPage  最終ページ
     * @return PaginationLinks ページネーションリンク
     */
    private function buildPaginationLinks(string $baseUrl, int $currentPage, int $lastPage): PaginationLinks
    {
        if (empty($baseUrl)) {
            return new PaginationLinks(
                first: null,
                last: null,
                prev: null,
                next: null,
            );
        }

        return new PaginationLinks(
            first: $baseUrl.'?page=1',
            last: $baseUrl.'?page='.$lastPage,
            prev: $currentPage > 1 ? $baseUrl.'?page='.($currentPage - 1) : null,
            next: $currentPage < $lastPage ? $baseUrl.'?page='.($currentPage + 1) : null,
        );
    }
}
