<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\DTO;

use InvalidArgumentException;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;

/**
 * 蔵書検索条件
 *
 * 蔵書検索のパラメータを保持する不変オブジェクト。
 */
final readonly class BookSearchCriteria
{
    /**
     * 許可されたソートフィールド
     */
    private const ALLOWED_SORT_FIELDS = [
        'title',
        'author',
        'published_year',
        'created_at',
    ];

    /**
     * 許可されたソート方向
     */
    private const ALLOWED_SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * デフォルトページサイズ
     */
    public const DEFAULT_PAGE_SIZE = 20;

    /**
     * 最大ページサイズ
     */
    public const MAX_PAGE_SIZE = 100;

    /**
     * コンストラクタ
     *
     * @param  string|null  $title  タイトル（部分一致）
     * @param  string|null  $author  著者（部分一致）
     * @param  string|null  $isbn  ISBN（完全一致、ISBN-10またはISBN-13）
     * @param  string|null  $publisher  出版社（部分一致）
     * @param  string|null  $genre  ジャンル（完全一致）
     * @param  BookStatus|null  $status  ステータス（完全一致）
     * @param  int|null  $publishedYearFrom  出版年（From）
     * @param  int|null  $publishedYearTo  出版年（To）
     * @param  int  $page  ページ番号（1始まり）
     * @param  int  $pageSize  ページサイズ
     * @param  string  $sortField  ソートフィールド
     * @param  string  $sortDirection  ソート方向
     *
     * @throws InvalidArgumentException バリデーションエラーの場合
     */
    public function __construct(
        public ?string $title = null,
        public ?string $author = null,
        public ?string $isbn = null,
        public ?string $publisher = null,
        public ?string $genre = null,
        public ?BookStatus $status = null,
        public ?int $publishedYearFrom = null,
        public ?int $publishedYearTo = null,
        public int $page = 1,
        public int $pageSize = self::DEFAULT_PAGE_SIZE,
        public string $sortField = 'title',
        public string $sortDirection = 'asc',
    ) {
        // バリデーション
        if ($page < 1) {
            throw new InvalidArgumentException('ページ番号は1以上である必要があります');
        }

        if ($pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new InvalidArgumentException(
                sprintf('ページサイズは1〜%dの範囲である必要があります', self::MAX_PAGE_SIZE)
            );
        }

        if (! in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
            throw new InvalidArgumentException(
                sprintf('無効なソートフィールド: %s', $sortField)
            );
        }

        if (! in_array($sortDirection, self::ALLOWED_SORT_DIRECTIONS, true)) {
            throw new InvalidArgumentException(
                sprintf('無効なソート方向: %s', $sortDirection)
            );
        }
    }

    /**
     * 検索条件が空か判定
     */
    public function isEmpty(): bool
    {
        return $this->title === null
            && $this->author === null
            && $this->isbn === null
            && $this->publisher === null
            && $this->genre === null
            && $this->status === null
            && $this->publishedYearFrom === null
            && $this->publishedYearTo === null;
    }

    /**
     * オフセットを計算
     */
    public function offset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }
}
