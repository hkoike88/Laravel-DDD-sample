<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\DTO;

use Packages\Domain\Book\Application\DTO\BookCollection;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use PHPUnit\Framework\TestCase;

/**
 * BookCollection ユニットテスト
 */
class BookCollectionTest extends TestCase
{
    /**
     * テスト用の蔵書エンティティを作成
     *
     * @param  string  $title  タイトル
     */
    private function createBook(string $title = 'テスト書籍'): Book
    {
        return Book::create(
            id: BookId::generate(),
            title: $title,
        );
    }

    // =========================================================================
    // コンストラクタのテスト
    // =========================================================================

    /**
     * @test
     * コレクションを生成できる
     */
    public function test_コレクションを生成できる(): void
    {
        // Arrange
        $items = [
            $this->createBook('書籍1'),
            $this->createBook('書籍2'),
            $this->createBook('書籍3'),
        ];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 100,
            currentPage: 2,
            totalPages: 10,
            pageSize: 10,
        );

        // Assert
        $this->assertCount(3, $collection->items);
        $this->assertSame(100, $collection->totalCount);
        $this->assertSame(2, $collection->currentPage);
        $this->assertSame(10, $collection->totalPages);
        $this->assertSame(10, $collection->pageSize);
    }

    // =========================================================================
    // empty のテスト
    // =========================================================================

    /**
     * @test
     * 空のコレクションを生成できる
     */
    public function test_空のコレクションを生成できる(): void
    {
        // Act
        $collection = BookCollection::empty();

        // Assert
        $this->assertEmpty($collection->items);
        $this->assertSame(0, $collection->totalCount);
        $this->assertSame(1, $collection->currentPage);
        $this->assertSame(0, $collection->totalPages);
        $this->assertSame(BookSearchCriteria::DEFAULT_PAGE_SIZE, $collection->pageSize);
    }

    /**
     * @test
     * カスタムページサイズで空のコレクションを生成できる
     */
    public function test_カスタムページサイズで空のコレクションを生成できる(): void
    {
        // Act
        $collection = BookCollection::empty(50);

        // Assert
        $this->assertSame(50, $collection->pageSize);
    }

    // =========================================================================
    // isEmpty のテスト
    // =========================================================================

    /**
     * @test
     * 空のコレクションでisEmptyはtrueを返す
     */
    public function test_空のコレクションでis_emptyはtrueを返す(): void
    {
        // Act
        $collection = BookCollection::empty();

        // Assert
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * @test
     * 要素があるコレクションでisEmptyはfalseを返す
     */
    public function test_要素があるコレクションでis_emptyはfalseを返す(): void
    {
        // Arrange
        $items = [$this->createBook()];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 1,
            currentPage: 1,
            totalPages: 1,
            pageSize: 20,
        );

        // Assert
        $this->assertFalse($collection->isEmpty());
    }

    // =========================================================================
    // count のテスト
    // =========================================================================

    /**
     * @test
     * 現在ページの蔵書件数を取得できる
     */
    public function test_現在ページの蔵書件数を取得できる(): void
    {
        // Arrange
        $items = [
            $this->createBook('書籍1'),
            $this->createBook('書籍2'),
            $this->createBook('書籍3'),
        ];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 100,
            currentPage: 1,
            totalPages: 10,
            pageSize: 10,
        );

        // Assert
        $this->assertSame(3, $collection->count());
    }

    /**
     * @test
     * 空のコレクションでcountは0を返す
     */
    public function test_空のコレクションでcountは0を返す(): void
    {
        // Act
        $collection = BookCollection::empty();

        // Assert
        $this->assertSame(0, $collection->count());
    }

    // =========================================================================
    // hasNextPage のテスト
    // =========================================================================

    /**
     * @test
     * 次のページが存在する場合trueを返す
     */
    public function test_次のページが存在する場合trueを返す(): void
    {
        // Arrange
        $items = [$this->createBook()];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 50,
            currentPage: 2,
            totalPages: 5,
            pageSize: 10,
        );

        // Assert
        $this->assertTrue($collection->hasNextPage());
    }

    /**
     * @test
     * 最終ページの場合hasNextPageはfalseを返す
     */
    public function test_最終ページの場合has_next_pageはfalseを返す(): void
    {
        // Arrange
        $items = [$this->createBook()];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 50,
            currentPage: 5,
            totalPages: 5,
            pageSize: 10,
        );

        // Assert
        $this->assertFalse($collection->hasNextPage());
    }

    /**
     * @test
     * 空のコレクションでhasNextPageはfalseを返す
     */
    public function test_空のコレクションでhas_next_pageはfalseを返す(): void
    {
        // Act
        $collection = BookCollection::empty();

        // Assert
        $this->assertFalse($collection->hasNextPage());
    }

    // =========================================================================
    // hasPreviousPage のテスト
    // =========================================================================

    /**
     * @test
     * 前のページが存在する場合trueを返す
     */
    public function test_前のページが存在する場合trueを返す(): void
    {
        // Arrange
        $items = [$this->createBook()];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 50,
            currentPage: 2,
            totalPages: 5,
            pageSize: 10,
        );

        // Assert
        $this->assertTrue($collection->hasPreviousPage());
    }

    /**
     * @test
     * 最初のページの場合hasPreviousPageはfalseを返す
     */
    public function test_最初のページの場合has_previous_pageはfalseを返す(): void
    {
        // Arrange
        $items = [$this->createBook()];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 50,
            currentPage: 1,
            totalPages: 5,
            pageSize: 10,
        );

        // Assert
        $this->assertFalse($collection->hasPreviousPage());
    }

    /**
     * @test
     * 空のコレクションでhasPreviousPageはfalseを返す
     */
    public function test_空のコレクションでhas_previous_pageはfalseを返す(): void
    {
        // Act
        $collection = BookCollection::empty();

        // Assert
        $this->assertFalse($collection->hasPreviousPage());
    }

    // =========================================================================
    // 1ページのみのテスト
    // =========================================================================

    /**
     * @test
     * 1ページのみの場合前後ページともfalseを返す
     */
    public function test_1ページのみの場合前後ページともfalseを返す(): void
    {
        // Arrange
        $items = [$this->createBook()];

        // Act
        $collection = new BookCollection(
            items: $items,
            totalCount: 5,
            currentPage: 1,
            totalPages: 1,
            pageSize: 10,
        );

        // Assert
        $this->assertFalse($collection->hasNextPage());
        $this->assertFalse($collection->hasPreviousPage());
    }
}
