<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\UseCases\Queries\SearchBooks;

/**
 * 蔵書検索クエリ
 *
 * 蔵書検索APIのリクエストパラメータを保持する入力DTO。
 * Presentation層からApplication層へのデータ転送に使用。
 */
final readonly class SearchBooksQuery
{
    /**
     * コンストラクタ
     *
     * @param  string|null  $title  タイトル検索キーワード（部分一致）
     * @param  string|null  $author  著者名検索キーワード（部分一致）
     * @param  string|null  $isbn  ISBN（完全一致、ISBN-10またはISBN-13）
     * @param  int  $page  ページ番号（1始まり）
     * @param  int  $perPage  1ページあたりの件数
     */
    public function __construct(
        public ?string $title = null,
        public ?string $author = null,
        public ?string $isbn = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
