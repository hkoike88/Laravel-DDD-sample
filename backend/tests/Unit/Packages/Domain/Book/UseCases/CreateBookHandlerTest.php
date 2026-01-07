<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\UseCases;

use Mockery;
use Mockery\MockInterface;
use Packages\Domain\Book\Application\UseCases\Commands\CreateBook\CreateBookCommand;
use Packages\Domain\Book\Application\UseCases\Commands\CreateBook\CreateBookHandler;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;
use Tests\TestCase;

/**
 * CreateBookHandler ユニットテスト
 *
 * CreateBookHandler の正常系・異常系をテストする。
 */
class CreateBookHandlerTest extends TestCase
{
    private BookRepositoryInterface&MockInterface $bookRepository;

    private CreateBookHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var BookRepositoryInterface&MockInterface $bookRepository */
        $bookRepository = Mockery::mock(BookRepositoryInterface::class);
        $this->bookRepository = $bookRepository;
        $this->handler = new CreateBookHandler($this->bookRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // T011: 正常系テスト
    // ========================================

    /**
     * タイトルのみで蔵書が作成できること
     */
    public function test_can_create_book_with_title_only(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: '吾輩は猫である',
            staffId: '01HQXYZ000000000STAFF01',
        );

        $this->bookRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Book::class));

        // Act
        $book = $this->handler->handle($command);

        // Assert
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('吾輩は猫である', $book->title());
        $this->assertNull($book->author());
        $this->assertNull($book->isbn());
        $this->assertNull($book->publisher());
        $this->assertNull($book->publishedYear());
        $this->assertNull($book->genre());
        $this->assertTrue($book->status()->equals(BookStatus::available()));
    }

    /**
     * 全項目で蔵書が作成できること
     */
    public function test_can_create_book_with_all_fields(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: '吾輩は猫である',
            staffId: '01HQXYZ000000000STAFF01',
            author: '夏目漱石',
            isbn: '9784003101018',
            publisher: '岩波書店',
            publishedYear: 1905,
            genre: '文学',
        );

        $this->bookRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Book::class));

        // Act
        $book = $this->handler->handle($command);

        // Assert
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('吾輩は猫である', $book->title());
        $this->assertEquals('夏目漱石', $book->author());
        $this->assertEquals('9784003101018', $book->isbn()?->value());
        $this->assertEquals('岩波書店', $book->publisher());
        $this->assertEquals(1905, $book->publishedYear());
        $this->assertEquals('文学', $book->genre());
        $this->assertTrue($book->status()->equals(BookStatus::available()));
    }

    /**
     * 作成された蔵書にULID形式のIDが付与されること
     */
    public function test_created_book_has_ulid_id(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: '吾輩は猫である',
            staffId: '01HQXYZ000000000STAFF01',
        );

        $this->bookRepository
            ->shouldReceive('save')
            ->once();

        // Act
        $book = $this->handler->handle($command);

        // Assert
        $id = $book->id()->value();
        $this->assertNotEmpty($id);
        $this->assertEquals(26, strlen($id));  // ULID is 26 characters
    }

    /**
     * リポジトリのsaveメソッドが呼び出されること
     */
    public function test_repository_save_is_called(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: '吾輩は猫である',
            staffId: '01HQXYZ000000000STAFF01',
        );

        $savedBook = null;
        $this->bookRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Book $book) use (&$savedBook) {
                $savedBook = $book;

                return true;
            }));

        // Act
        $book = $this->handler->handle($command);

        // Assert
        $this->assertSame($book, $savedBook);
    }

    /**
     * ISBN付きで蔵書が作成できること（ISBN-10形式）
     */
    public function test_can_create_book_with_isbn10(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: '吾輩は猫である',
            staffId: '01HQXYZ000000000STAFF01',
            isbn: '4003101014',  // ISBN-10
        );

        $this->bookRepository
            ->shouldReceive('save')
            ->once();

        // Act
        $book = $this->handler->handle($command);

        // Assert
        $this->assertEquals('4003101014', $book->isbn()?->value());
    }

    /**
     * ハイフン付きISBN-13で蔵書が作成できること
     */
    public function test_can_create_book_with_hyphenated_isbn13(): void
    {
        // Arrange
        $command = new CreateBookCommand(
            title: '吾輩は猫である',
            staffId: '01HQXYZ000000000STAFF01',
            isbn: '978-4-00-310101-8',  // ハイフン付きISBN-13
        );

        $this->bookRepository
            ->shouldReceive('save')
            ->once();

        // Act
        $book = $this->handler->handle($command);

        // Assert
        $this->assertEquals('9784003101018', $book->isbn()?->value());  // 正規化される
    }
}
