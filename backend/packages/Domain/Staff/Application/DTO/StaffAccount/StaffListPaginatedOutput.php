<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * ページネーション付き職員一覧レスポンス
 *
 * @feature EPIC-003-staff-account-create
 */
final readonly class StaffListPaginatedOutput
{
    /**
     * コンストラクタ
     *
     * @param  StaffListOutput[]  $data  職員一覧
     * @param  int  $currentPage  現在のページ番号
     * @param  int  $lastPage  最終ページ番号
     * @param  int  $perPage  1ページあたりの件数
     * @param  int  $total  総件数
     * @param  int|null  $from  現在のページの開始レコード番号
     * @param  int|null  $to  現在のページの終了レコード番号
     * @param  PaginationLinks  $links  ページネーションリンク
     */
    public function __construct(
        public array $data,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?int $from,
        public ?int $to,
        public PaginationLinks $links,
    ) {}

    /**
     * 配列に変換（JSON レスポンス用）
     *
     * @return array{
     *   data: array<int, array{id: string, name: string, email: string, role: string, createdAt: string}>,
     *   meta: array{currentPage: int, lastPage: int, perPage: int, total: int, from: int|null, to: int|null},
     *   links: array{first: string|null, last: string|null, prev: string|null, next: string|null}
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(fn (StaffListOutput $item) => $item->toArray(), $this->data),
            'meta' => [
                'currentPage' => $this->currentPage,
                'lastPage' => $this->lastPage,
                'perPage' => $this->perPage,
                'total' => $this->total,
                'from' => $this->from,
                'to' => $this->to,
            ],
            'links' => $this->links->toArray(),
        ];
    }
}
