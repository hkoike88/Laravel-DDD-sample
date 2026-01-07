<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\DTO;

use InvalidArgumentException;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;
use PHPUnit\Framework\TestCase;

/**
 * BookSearchCriteria ユニットテスト
 */
class BookSearchCriteriaTest extends TestCase
{
    // =========================================================================
    // デフォルト値のテスト
    // =========================================================================

    /**
     * @test
     * デフォルト値で生成できる
     */
    public function test_デフォルト値で生成できる(): void
    {
        // Act
        $criteria = new BookSearchCriteria;

        // Assert
        $this->assertNull($criteria->title);
        $this->assertNull($criteria->author);
        $this->assertNull($criteria->publisher);
        $this->assertNull($criteria->genre);
        $this->assertNull($criteria->status);
        $this->assertNull($criteria->publishedYearFrom);
        $this->assertNull($criteria->publishedYearTo);
        $this->assertSame(1, $criteria->page);
        $this->assertSame(BookSearchCriteria::DEFAULT_PAGE_SIZE, $criteria->pageSize);
        $this->assertSame('title', $criteria->sortField);
        $this->assertSame('asc', $criteria->sortDirection);
    }

    // =========================================================================
    // 検索条件のテスト
    // =========================================================================

    /**
     * @test
     * 全ての検索条件を指定できる
     */
    public function test_全ての検索条件を指定できる(): void
    {
        // Arrange
        $status = BookStatus::available();

        // Act
        $criteria = new BookSearchCriteria(
            title: 'テスト',
            author: '著者',
            publisher: '出版社',
            genre: '技術書',
            status: $status,
            publishedYearFrom: 2020,
            publishedYearTo: 2024,
            page: 2,
            pageSize: 50,
            sortField: 'author',
            sortDirection: 'desc',
        );

        // Assert
        $this->assertSame('テスト', $criteria->title);
        $this->assertSame('著者', $criteria->author);
        $this->assertSame('出版社', $criteria->publisher);
        $this->assertSame('技術書', $criteria->genre);
        $this->assertTrue($criteria->status->equals($status));
        $this->assertSame(2020, $criteria->publishedYearFrom);
        $this->assertSame(2024, $criteria->publishedYearTo);
        $this->assertSame(2, $criteria->page);
        $this->assertSame(50, $criteria->pageSize);
        $this->assertSame('author', $criteria->sortField);
        $this->assertSame('desc', $criteria->sortDirection);
    }

    // =========================================================================
    // isEmpty のテスト
    // =========================================================================

    /**
     * @test
     * 検索条件が空の場合trueを返す
     */
    public function test_検索条件が空の場合trueを返す(): void
    {
        // Act
        $criteria = new BookSearchCriteria;

        // Assert
        $this->assertTrue($criteria->isEmpty());
    }

    /**
     * @test
     * タイトルが指定されている場合isEmptyはfalseを返す
     */
    public function test_タイトルが指定されている場合is_emptyはfalseを返す(): void
    {
        // Act
        $criteria = new BookSearchCriteria(title: 'テスト');

        // Assert
        $this->assertFalse($criteria->isEmpty());
    }

    /**
     * @test
     * ステータスが指定されている場合isEmptyはfalseを返す
     */
    public function test_ステータスが指定されている場合is_emptyはfalseを返す(): void
    {
        // Act
        $criteria = new BookSearchCriteria(status: BookStatus::available());

        // Assert
        $this->assertFalse($criteria->isEmpty());
    }

    // =========================================================================
    // offset のテスト
    // =========================================================================

    /**
     * @test
     * ページ1の場合オフセットは0
     */
    public function test_ページ1の場合オフセットは0(): void
    {
        // Act
        $criteria = new BookSearchCriteria(page: 1, pageSize: 20);

        // Assert
        $this->assertSame(0, $criteria->offset());
    }

    /**
     * @test
     * ページ2の場合オフセットはpageSize
     */
    public function test_ページ2の場合オフセットはpage_size(): void
    {
        // Act
        $criteria = new BookSearchCriteria(page: 2, pageSize: 20);

        // Assert
        $this->assertSame(20, $criteria->offset());
    }

    /**
     * @test
     * ページ3でpageSize10の場合オフセットは20
     */
    public function test_ページ3でpage_size10の場合オフセットは20(): void
    {
        // Act
        $criteria = new BookSearchCriteria(page: 3, pageSize: 10);

        // Assert
        $this->assertSame(20, $criteria->offset());
    }

    // =========================================================================
    // バリデーションのテスト
    // =========================================================================

    /**
     * @test
     * ページ番号が0以下の場合例外がスローされる
     */
    public function test_ページ番号が0以下の場合例外がスローされる(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ページ番号は1以上である必要があります');

        // Act
        new BookSearchCriteria(page: 0);
    }

    /**
     * @test
     * ページサイズが0以下の場合例外がスローされる
     */
    public function test_ページサイズが0以下の場合例外がスローされる(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ページサイズは1〜100の範囲である必要があります');

        // Act
        new BookSearchCriteria(pageSize: 0);
    }

    /**
     * @test
     * ページサイズが上限を超える場合例外がスローされる
     */
    public function test_ページサイズが上限を超える場合例外がスローされる(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ページサイズは1〜100の範囲である必要があります');

        // Act
        new BookSearchCriteria(pageSize: 101);
    }

    /**
     * @test
     * ページサイズが最大値の場合は正常に生成される
     */
    public function test_ページサイズが最大値の場合は正常に生成される(): void
    {
        // Act
        $criteria = new BookSearchCriteria(pageSize: BookSearchCriteria::MAX_PAGE_SIZE);

        // Assert
        $this->assertSame(100, $criteria->pageSize);
    }

    /**
     * @test
     * 無効なソートフィールドの場合例外がスローされる
     */
    public function test_無効なソートフィールドの場合例外がスローされる(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無効なソートフィールド: invalid');

        // Act
        new BookSearchCriteria(sortField: 'invalid');
    }

    /**
     * @test
     * 無効なソート方向の場合例外がスローされる
     */
    public function test_無効なソート方向の場合例外がスローされる(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無効なソート方向: invalid');

        // Act
        new BookSearchCriteria(sortDirection: 'invalid');
    }

    // =========================================================================
    // 有効なソートフィールドのテスト
    // =========================================================================

    /**
     * @test
     *
     * @dataProvider validSortFieldsProvider
     * 有効なソートフィールドで生成できる
     */
    public function test_有効なソートフィールドで生成できる(string $sortField): void
    {
        // Act
        $criteria = new BookSearchCriteria(sortField: $sortField);

        // Assert
        $this->assertSame($sortField, $criteria->sortField);
    }

    /**
     * 有効なソートフィールドのデータプロバイダー
     *
     * @return array<string, array{string}>
     */
    public static function validSortFieldsProvider(): array
    {
        return [
            'title' => ['title'],
            'author' => ['author'],
            'published_year' => ['published_year'],
            'created_at' => ['created_at'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider validSortDirectionsProvider
     * 有効なソート方向で生成できる
     */
    public function test_有効なソート方向で生成できる(string $sortDirection): void
    {
        // Act
        $criteria = new BookSearchCriteria(sortDirection: $sortDirection);

        // Assert
        $this->assertSame($sortDirection, $criteria->sortDirection);
    }

    /**
     * 有効なソート方向のデータプロバイダー
     *
     * @return array<string, array{string}>
     */
    public static function validSortDirectionsProvider(): array
    {
        return [
            'asc' => ['asc'],
            'desc' => ['desc'],
        ];
    }
}
