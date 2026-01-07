<?php

declare(strict_types=1);

namespace Tests\Integration\Packages\Domain\Book\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Application\Repositories\EloquentBookRepository;
use Packages\Domain\Book\Domain\Exceptions\BookNotFoundException;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;
use Tests\TestCase;

/**
 * EloquentBookRepository 統合テスト
 */
class EloquentBookRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentBookRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentBookRepository;
    }

    /**
     * テスト用の蔵書エンティティを作成
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createBook(array $overrides = []): Book
    {
        return Book::create(
            id: $overrides['id'] ?? BookId::generate(),
            title: $overrides['title'] ?? 'テスト書籍',
            author: $overrides['author'] ?? 'テスト著者',
            isbn: isset($overrides['isbn'])
                ? ($overrides['isbn'] instanceof ISBN ? $overrides['isbn'] : ISBN::fromString($overrides['isbn']))
                : ISBN::fromString('978-4-7981-2196-3'),
            publisher: $overrides['publisher'] ?? 'テスト出版社',
            publishedYear: $overrides['publishedYear'] ?? 2024,
            genre: $overrides['genre'] ?? '技術書',
        );
    }

    // =========================================================================
    // User Story 1: 蔵書の永続化
    // =========================================================================

    /**
     * @test
     * 蔵書を保存して取得できる
     */
    public function test_蔵書を保存して取得できる(): void
    {
        // Arrange
        $book = $this->createBook();

        // Act
        $this->repository->save($book);
        $found = $this->repository->find($book->id());

        // Assert
        $this->assertTrue($book->id()->equals($found->id()));
        $this->assertSame($book->title(), $found->title());
        $this->assertSame($book->author(), $found->author());
        $this->assertTrue($book->isbn()->equals($found->isbn()));
        $this->assertSame($book->publisher(), $found->publisher());
        $this->assertSame($book->publishedYear(), $found->publishedYear());
        $this->assertSame($book->genre(), $found->genre());
        $this->assertTrue($book->status()->equals($found->status()));
    }

    /**
     * @test
     * ISBN なしの蔵書を保存して取得できる
     */
    public function test_isb_nなしの蔵書を保存して取得できる(): void
    {
        // Arrange
        $book = Book::create(
            id: BookId::generate(),
            title: 'ISBN なし書籍',
        );

        // Act
        $this->repository->save($book);
        $found = $this->repository->find($book->id());

        // Assert
        $this->assertTrue($book->id()->equals($found->id()));
        $this->assertSame($book->title(), $found->title());
        $this->assertNull($found->author());
        $this->assertNull($found->isbn());
        $this->assertNull($found->publisher());
        $this->assertNull($found->publishedYear());
        $this->assertNull($found->genre());
    }

    /**
     * @test
     * 存在しない蔵書IDで検索するとnullが返る（findOrNull）
     */
    public function test_存在しない蔵書_i_dで検索するとnullが返る(): void
    {
        // Act
        $result = $this->repository->findOrNull(BookId::generate());

        // Assert
        $this->assertNull($result);
    }

    /**
     * @test
     * 存在しない蔵書IDで検索すると例外がスローされる（find）
     */
    public function test_存在しない蔵書_i_dで検索すると例外がスローされる(): void
    {
        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->repository->find(BookId::generate());
    }

    // =========================================================================
    // User Story 2: ISBN による蔵書検索
    // =========================================================================

    /**
     * @test
     * ISBN で蔵書を検索できる（複本対応）
     */
    public function test_isb_nで蔵書を検索できる(): void
    {
        // Arrange
        $isbn = ISBN::fromString('978-4-7981-2196-3');
        $book1 = $this->createBook(['isbn' => $isbn]);
        $book2 = $this->createBook(['isbn' => $isbn, 'title' => 'コピー1']);
        $book3 = $this->createBook(['isbn' => $isbn, 'title' => 'コピー2']);
        $otherBook = $this->createBook(['isbn' => ISBN::fromString('978-0-13-468599-1')]);

        $this->repository->save($book1);
        $this->repository->save($book2);
        $this->repository->save($book3);
        $this->repository->save($otherBook);

        // Act
        $results = $this->repository->findByIsbn($isbn);

        // Assert
        $this->assertCount(3, $results);
    }

    /**
     * @test
     * 該当する ISBN の蔵書が存在しない場合は空配列が返る
     */
    public function test_該当する_isb_nの蔵書が存在しない場合は空配列が返る(): void
    {
        // Act
        $results = $this->repository->findByIsbn(ISBN::fromString('978-4-7981-2196-3'));

        // Assert
        $this->assertEmpty($results);
    }

    // =========================================================================
    // User Story 3: 条件指定による蔵書検索
    // =========================================================================

    /**
     * @test
     * タイトルで部分一致検索できる
     */
    public function test_タイトルで部分一致検索できる(): void
    {
        // Arrange
        $this->repository->save($this->createBook(['title' => 'ドメイン駆動設計入門']));
        $this->repository->save($this->createBook(['title' => 'ドメイン駆動設計実践']));
        $this->repository->save($this->createBook(['title' => 'クリーンアーキテクチャ']));

        // Act
        $criteria = new BookSearchCriteria(title: 'ドメイン駆動');
        $collection = $this->repository->search($criteria);

        // Assert
        $this->assertSame(2, $collection->totalCount);
    }

    /**
     * @test
     * ステータスで検索できる
     */
    public function test_ステータスで検索できる(): void
    {
        // Arrange
        $book1 = $this->createBook(['title' => '利用可能な本']);
        $book2 = $this->createBook(['title' => '貸出中の本']);
        $book2->borrow();

        $this->repository->save($book1);
        $this->repository->save($book2);

        // Act
        $criteria = new BookSearchCriteria(status: BookStatus::available());
        $collection = $this->repository->search($criteria);

        // Assert
        $this->assertSame(1, $collection->totalCount);
        $this->assertSame('利用可能な本', $collection->items[0]->title());
    }

    /**
     * @test
     * 複数条件をANDで組み合わせて検索できる
     */
    public function test_複数条件を_an_dで組み合わせて検索できる(): void
    {
        // Arrange
        $this->repository->save($this->createBook([
            'title' => 'ドメイン駆動設計入門',
            'author' => 'エヴァンス',
        ]));
        $this->repository->save($this->createBook([
            'title' => 'ドメイン駆動設計実践',
            'author' => '増田',
        ]));
        $this->repository->save($this->createBook([
            'title' => 'クリーンアーキテクチャ',
            'author' => 'マーチン',
        ]));

        // Act
        $criteria = new BookSearchCriteria(
            title: 'ドメイン駆動',
            author: 'エヴァンス',
        );
        $collection = $this->repository->search($criteria);

        // Assert
        $this->assertSame(1, $collection->totalCount);
        $this->assertSame('ドメイン駆動設計入門', $collection->items[0]->title());
    }

    /**
     * @test
     * ページネーションが正しく動作する
     */
    public function test_ページネーションが正しく動作する(): void
    {
        // Arrange
        for ($i = 1; $i <= 25; $i++) {
            $this->repository->save($this->createBook(['title' => "テスト書籍{$i}"]));
        }

        // Act
        $criteria = new BookSearchCriteria(pageSize: 10, page: 2);
        $collection = $this->repository->search($criteria);

        // Assert
        $this->assertSame(25, $collection->totalCount);
        $this->assertSame(10, $collection->count());
        $this->assertSame(2, $collection->currentPage);
        $this->assertSame(3, $collection->totalPages);
        $this->assertTrue($collection->hasNextPage());
        $this->assertTrue($collection->hasPreviousPage());
    }

    /**
     * @test
     * ソートが正しく動作する
     */
    public function test_ソートが正しく動作する(): void
    {
        // Arrange
        $this->repository->save($this->createBook(['title' => 'C の本']));
        $this->repository->save($this->createBook(['title' => 'A の本']));
        $this->repository->save($this->createBook(['title' => 'B の本']));

        // Act
        $criteria = new BookSearchCriteria(sortField: 'title', sortDirection: 'asc');
        $collection = $this->repository->search($criteria);

        // Assert
        $this->assertSame('A の本', $collection->items[0]->title());
        $this->assertSame('B の本', $collection->items[1]->title());
        $this->assertSame('C の本', $collection->items[2]->title());
    }

    // =========================================================================
    // User Story 4: 蔵書情報の更新
    // =========================================================================

    /**
     * @test
     * 蔵書のステータスを更新できる
     */
    public function test_蔵書のステータスを更新できる(): void
    {
        // Arrange
        $book = $this->createBook();
        $this->repository->save($book);

        // Act
        $book->borrow();
        $this->repository->save($book);
        $found = $this->repository->find($book->id());

        // Assert
        $this->assertTrue($found->status()->isBorrowed());
    }

    /**
     * @test
     * 既存の蔵書を更新しても新しいレコードは作成されない
     */
    public function test_既存の蔵書を更新しても新しいレコードは作成されない(): void
    {
        // Arrange
        $book = $this->createBook();
        $this->repository->save($book);

        // Act
        $book->borrow();
        $this->repository->save($book);

        // Assert
        $criteria = new BookSearchCriteria;
        $this->assertSame(1, $this->repository->count($criteria));
    }

    // =========================================================================
    // User Story 5: 蔵書の削除
    // =========================================================================

    /**
     * @test
     * 蔵書を削除できる
     */
    public function test_蔵書を削除できる(): void
    {
        // Arrange
        $book = $this->createBook();
        $this->repository->save($book);

        // Act
        $this->repository->delete($book->id());

        // Assert
        $this->assertNull($this->repository->findOrNull($book->id()));
    }

    /**
     * @test
     * 存在しない蔵書IDで削除してもエラーにならない（冪等性）
     */
    public function test_存在しない蔵書_i_dで削除してもエラーにならない(): void
    {
        // Arrange
        $nonExistentId = BookId::generate();

        // Act & Assert (例外がスローされないことを確認)
        $this->repository->delete($nonExistentId);
        $this->assertTrue(true);
    }

    // =========================================================================
    // User Story 6: 検索結果件数の取得
    // =========================================================================

    /**
     * @test
     * 検索条件に一致する蔵書の件数を取得できる
     */
    public function test_検索条件に一致する蔵書の件数を取得できる(): void
    {
        // Arrange
        $this->repository->save($this->createBook(['genre' => '技術書']));
        $this->repository->save($this->createBook(['genre' => '技術書']));
        $this->repository->save($this->createBook(['genre' => '小説']));

        // Act
        $criteria = new BookSearchCriteria(genre: '技術書');
        $count = $this->repository->count($criteria);

        // Assert
        $this->assertSame(2, $count);
    }

    /**
     * @test
     * 条件に一致する蔵書がない場合は0が返る
     */
    public function test_条件に一致する蔵書がない場合は0が返る(): void
    {
        // Act
        $criteria = new BookSearchCriteria(genre: '存在しないジャンル');
        $count = $this->repository->count($criteria);

        // Assert
        $this->assertSame(0, $count);
    }
}
