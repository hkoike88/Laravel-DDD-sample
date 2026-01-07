<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Packages\Domain\Book\Application\DTO\BookCollection;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\Exceptions\BookNotFoundException;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;

/**
 * Eloquent 蔵書リポジトリ実装
 *
 * BookRepositoryInterface の Eloquent ORM を使用した実装。
 * ドメインモデルと永続化層の変換を担当。
 */
class EloquentBookRepository implements BookRepositoryInterface
{
    /**
     * ID で蔵書を取得
     *
     * @param  BookId  $id  蔵書ID
     * @return Book 蔵書エンティティ
     *
     * @throws BookNotFoundException 蔵書が存在しない場合
     */
    public function find(BookId $id): Book
    {
        $record = BookRecord::find($id->value());

        if ($record === null) {
            throw new BookNotFoundException($id);
        }

        return $this->toDomain($record);
    }

    /**
     * ID で蔵書を取得（存在しない場合は null）
     *
     * @param  BookId  $id  蔵書ID
     * @return Book|null 蔵書エンティティまたはnull
     */
    public function findOrNull(BookId $id): ?Book
    {
        $record = BookRecord::find($id->value());

        if ($record === null) {
            return null;
        }

        return $this->toDomain($record);
    }

    /**
     * ISBN で蔵書を検索（複本対応）
     *
     * @param  ISBN  $isbn  ISBN
     * @return list<Book> 蔵書エンティティのリスト
     */
    public function findByIsbn(ISBN $isbn): array
    {
        $records = BookRecord::where('isbn', $isbn->value())->get();

        return array_values($records->map(fn (BookRecord $record) => $this->toDomain($record))->all());
    }

    /**
     * 条件で蔵書を検索
     *
     * @param  BookSearchCriteria  $criteria  検索条件
     * @return BookCollection 検索結果コレクション
     */
    public function search(BookSearchCriteria $criteria): BookCollection
    {
        $query = BookRecord::query();

        $this->applySearchCriteria($query, $criteria);

        // 総件数を取得
        $totalCount = $query->count();

        if ($totalCount === 0) {
            return BookCollection::empty($criteria->pageSize);
        }

        // ソートを適用
        $sortField = $this->mapSortField($criteria->sortField);
        $query->orderBy($sortField, $criteria->sortDirection);

        // ページネーションを適用
        $records = $query
            ->offset($criteria->offset())
            ->limit($criteria->pageSize)
            ->get();

        // ドメインモデルに変換
        $items = array_values($records->map(fn (BookRecord $record) => $this->toDomain($record))->all());

        // 総ページ数を計算
        $totalPages = (int) ceil($totalCount / $criteria->pageSize);

        return new BookCollection(
            items: $items,
            totalCount: $totalCount,
            currentPage: $criteria->page,
            totalPages: $totalPages,
            pageSize: $criteria->pageSize,
        );
    }

    /**
     * 条件に一致する蔵書の件数を取得
     *
     * @param  BookSearchCriteria  $criteria  検索条件
     * @return int 件数
     */
    public function count(BookSearchCriteria $criteria): int
    {
        $query = BookRecord::query();

        $this->applySearchCriteria($query, $criteria);

        return $query->count();
    }

    /**
     * 蔵書を保存（新規作成または更新）
     *
     * @param  Book  $book  蔵書エンティティ
     */
    public function save(Book $book): void
    {
        BookRecord::updateOrCreate(
            ['id' => $book->id()->value()],
            [
                'title' => $book->title(),
                'author' => $book->author(),
                'isbn' => $book->isbn()?->value(),
                'publisher' => $book->publisher(),
                'published_year' => $book->publishedYear(),
                'genre' => $book->genre(),
                'status' => $book->status()->value(),
                'registered_by' => $book->registeredBy(),
                'registered_at' => $book->registeredAt(),
            ]
        );
    }

    /**
     * 蔵書を削除
     *
     * @param  BookId  $id  蔵書ID
     */
    public function delete(BookId $id): void
    {
        BookRecord::where('id', $id->value())->delete();
    }

    /**
     * Eloquent モデルをドメインモデルに変換
     *
     * @param  BookRecord  $record  Eloquent モデル
     * @return Book ドメインモデル
     */
    private function toDomain(BookRecord $record): Book
    {
        return Book::reconstruct(
            id: BookId::fromString($record->id),
            title: $record->title,
            author: $record->author,
            isbn: $record->isbn !== null ? ISBN::fromString($record->isbn) : null,
            publisher: $record->publisher,
            publishedYear: $record->published_year,
            genre: $record->genre,
            status: BookStatus::from($record->status),
            registeredBy: $record->registered_by,
            registeredAt: $record->registered_at !== null
                ? \DateTimeImmutable::createFromInterface($record->registered_at)
                : null,
        );
    }

    /**
     * 検索条件をクエリに適用
     *
     * @param  Builder<BookRecord>  $query  クエリビルダー
     * @param  BookSearchCriteria  $criteria  検索条件
     */
    private function applySearchCriteria(Builder $query, BookSearchCriteria $criteria): void
    {
        // タイトル（部分一致）
        if ($criteria->title !== null) {
            $query->where('title', 'LIKE', '%'.$criteria->title.'%');
        }

        // 著者（部分一致）
        if ($criteria->author !== null) {
            $query->where('author', 'LIKE', '%'.$criteria->author.'%');
        }

        // ISBN（完全一致）
        if ($criteria->isbn !== null) {
            $query->where('isbn', $criteria->isbn);
        }

        // 出版社（部分一致）
        if ($criteria->publisher !== null) {
            $query->where('publisher', 'LIKE', '%'.$criteria->publisher.'%');
        }

        // ジャンル（完全一致）
        if ($criteria->genre !== null) {
            $query->where('genre', $criteria->genre);
        }

        // ステータス（完全一致）
        if ($criteria->status !== null) {
            $query->where('status', $criteria->status->value());
        }

        // 出版年（範囲）
        if ($criteria->publishedYearFrom !== null) {
            $query->where('published_year', '>=', $criteria->publishedYearFrom);
        }

        if ($criteria->publishedYearTo !== null) {
            $query->where('published_year', '<=', $criteria->publishedYearTo);
        }
    }

    /**
     * ソートフィールド名をDBカラム名にマッピング
     *
     * @param  string  $sortField  ソートフィールド名
     * @return string DBカラム名
     */
    private function mapSortField(string $sortField): string
    {
        return match ($sortField) {
            'title' => 'title',
            'author' => 'author',
            'published_year' => 'published_year',
            'created_at' => 'created_at',
            default => 'title',
        };
    }
}
