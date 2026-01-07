<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\UseCases\Queries\SearchBooks;

use Packages\Domain\Book\Application\DTO\BookCollection;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;

/**
 * 蔵書検索ハンドラ
 *
 * 蔵書検索クエリを処理し、検索結果を返却するユースケース。
 */
final readonly class SearchBooksHandler
{
    /**
     * コンストラクタ
     *
     * @param  BookRepositoryInterface  $bookRepository  蔵書リポジトリ
     */
    public function __construct(
        private BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * 蔵書検索を実行
     *
     * @param  SearchBooksQuery  $query  検索クエリ
     * @return BookCollection 検索結果コレクション
     */
    public function handle(SearchBooksQuery $query): BookCollection
    {
        $criteria = new BookSearchCriteria(
            title: $query->title,
            author: $query->author,
            isbn: $query->isbn,
            page: $query->page,
            pageSize: $query->perPage,
        );

        return $this->bookRepository->search($criteria);
    }
}
