<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Repositories;

use Packages\Domain\Book\Application\DTO\BookCollection;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

/**
 * 蔵書リポジトリインターフェース
 *
 * 蔵書の永続化と取得を抽象化するインターフェース。
 * ドメイン層とインフラストラクチャ層を分離し、データアクセスを抽象化する。
 */
interface BookRepositoryInterface
{
    /**
     * ID で蔵書を取得
     *
     * 指定されたIDの蔵書を取得する。存在しない場合は例外をスロー。
     *
     * @param BookId $id 蔵書ID
     * @return Book 蔵書エンティティ
     * @throws \Packages\Domain\Book\Domain\Exceptions\BookNotFoundException 蔵書が存在しない場合
     */
    public function find(BookId $id): Book;

    /**
     * ID で蔵書を取得（存在しない場合は null）
     *
     * 指定されたIDの蔵書を取得する。存在しない場合はnullを返す。
     *
     * @param BookId $id 蔵書ID
     * @return Book|null 蔵書エンティティまたはnull
     */
    public function findOrNull(BookId $id): ?Book;

    /**
     * ISBN で蔵書を検索（複本対応）
     *
     * 同一ISBNを持つ蔵書を全て取得する（複本管理対応）。
     * ISBNがnullの蔵書は結果に含まれない。
     *
     * @param ISBN $isbn ISBN
     * @return list<Book> 蔵書エンティティのリスト（該当なしの場合は空配列）
     */
    public function findByIsbn(ISBN $isbn): array;

    /**
     * 条件で蔵書を検索
     *
     * 指定された検索条件に一致する蔵書をページネーション付きで取得する。
     * 複数条件はAND条件で組み合わせられる。
     *
     * @param BookSearchCriteria $criteria 検索条件
     * @return BookCollection 検索結果コレクション
     */
    public function search(BookSearchCriteria $criteria): BookCollection;

    /**
     * 条件に一致する蔵書の件数を取得
     *
     * 指定された検索条件に一致する蔵書の総件数を取得する。
     * ページネーションのUI表示に使用。
     *
     * @param BookSearchCriteria $criteria 検索条件
     * @return int 件数
     */
    public function count(BookSearchCriteria $criteria): int;

    /**
     * 蔵書を保存（新規作成または更新）
     *
     * 蔵書エンティティをデータベースに永続化する。
     * IDが既存の場合は更新、新規の場合は挿入。
     *
     * @param Book $book 蔵書エンティティ
     * @return void
     */
    public function save(Book $book): void;

    /**
     * 蔵書を削除
     *
     * 指定されたIDの蔵書を削除する。
     * 存在しないIDを指定してもエラーは発生しない（冪等性保証）。
     *
     * @param BookId $id 蔵書ID
     * @return void
     */
    public function delete(BookId $id): void;
}
