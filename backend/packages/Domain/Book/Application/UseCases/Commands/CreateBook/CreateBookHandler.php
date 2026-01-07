<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\UseCases\Commands\CreateBook;

use DateTimeImmutable;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

/**
 * 蔵書登録ハンドラ
 *
 * 蔵書登録コマンドを処理し、新規蔵書を作成・永続化するユースケース。
 * 登録者情報（職員ID・登録日時）を記録する。
 */
final readonly class CreateBookHandler
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
     * 蔵書登録を実行
     *
     * @param  CreateBookCommand  $command  登録コマンド
     * @return Book 登録された蔵書エンティティ
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\EmptyBookTitleException タイトルが空の場合
     * @throws \Packages\Domain\Book\Domain\Exceptions\InvalidISBNException ISBNが不正な場合
     */
    public function handle(CreateBookCommand $command): Book
    {
        $isbn = $command->isbn !== null
            ? ISBN::fromString($command->isbn)
            : null;

        $book = Book::create(
            id: BookId::generate(),
            title: $command->title,
            author: $command->author,
            isbn: $isbn,
            publisher: $command->publisher,
            publishedYear: $command->publishedYear,
            genre: $command->genre,
            registeredBy: $command->staffId,
            registeredAt: new DateTimeImmutable,
        );

        $this->bookRepository->save($book);

        return $book;
    }
}
