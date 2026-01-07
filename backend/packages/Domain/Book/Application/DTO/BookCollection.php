<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\DTO;

use Packages\Domain\Book\Domain\Model\Book;

/**
 * 蔵書コレクション
 *
 * 検索結果の蔵書リストとページネーション情報を保持。
 */
final readonly class BookCollection
{
    /**
     * コンストラクタ
     *
     * @param  list<Book>  $items  蔵書リスト
     * @param  int  $totalCount  総件数
     * @param  int  $currentPage  現在のページ番号
     * @param  int  $totalPages  総ページ数
     * @param  int  $pageSize  ページサイズ
     */
    public function __construct(
        public array $items,
        public int $totalCount,
        public int $currentPage,
        public int $totalPages,
        public int $pageSize,
    ) {}

    /**
     * 空のコレクションを生成
     *
     * @param  int  $pageSize  ページサイズ
     */
    public static function empty(int $pageSize = BookSearchCriteria::DEFAULT_PAGE_SIZE): self
    {
        return new self(
            items: [],
            totalCount: 0,
            currentPage: 1,
            totalPages: 0,
            pageSize: $pageSize,
        );
    }

    /**
     * コレクションが空か判定
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * 次のページが存在するか判定
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * 前のページが存在するか判定
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * 蔵書の件数を取得
     */
    public function count(): int
    {
        return count($this->items);
    }
}
