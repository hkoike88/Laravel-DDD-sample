<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\Model;

use Packages\Domain\Book\Domain\Exceptions\EmptyBookTitleException;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

/**
 * Book エンティティのテスト
 */
describe('Book', function () {
    describe('create', function () {
        it('必須属性のみで蔵書を作成できること', function () {
            $id = BookId::generate();
            $title = 'ドメイン駆動設計入門';

            $book = Book::create(
                id: $id,
                title: $title,
            );

            expect($book)->toBeInstanceOf(Book::class);
            expect($book->id()->equals($id))->toBeTrue();
            expect($book->title())->toBe($title);
            expect($book->author())->toBeNull();
            expect($book->isbn())->toBeNull();
            expect($book->publisher())->toBeNull();
            expect($book->publishedYear())->toBeNull();
            expect($book->genre())->toBeNull();
            expect($book->status()->isAvailable())->toBeTrue();
        });

        it('全属性を指定して蔵書を作成できること', function () {
            $id = BookId::generate();
            $title = 'ドメイン駆動設計入門';
            $author = 'エリック・エヴァンス';
            $isbn = ISBN::fromString('978-4-7981-2196-3');
            $publisher = '翔泳社';
            $publishedYear = 2011;
            $genre = '技術書';

            $book = Book::create(
                id: $id,
                title: $title,
                author: $author,
                isbn: $isbn,
                publisher: $publisher,
                publishedYear: $publishedYear,
                genre: $genre,
            );

            expect($book->id()->equals($id))->toBeTrue();
            expect($book->title())->toBe($title);
            expect($book->author())->toBe($author);
            expect($book->isbn()->equals($isbn))->toBeTrue();
            expect($book->publisher())->toBe($publisher);
            expect($book->publishedYear())->toBe($publishedYear);
            expect($book->genre())->toBe($genre);
            expect($book->status()->isAvailable())->toBeTrue();
        });

        it('タイトルが空の場合は例外がスローされること', function () {
            $id = BookId::generate();

            expect(fn () => Book::create(
                id: $id,
                title: '',
            ))->toThrow(EmptyBookTitleException::class);
        });

        it('タイトルが空白のみの場合は例外がスローされること', function () {
            $id = BookId::generate();

            expect(fn () => Book::create(
                id: $id,
                title: '   ',
            ))->toThrow(EmptyBookTitleException::class);
        });

        it('初期状態は available であること', function () {
            $book = Book::create(
                id: BookId::generate(),
                title: 'テスト書籍',
            );

            expect($book->status()->value())->toBe('available');
            expect($book->isAvailable())->toBeTrue();
        });
    });

    describe('isAvailable', function () {
        it('available 状態の場合は true を返すこと', function () {
            $book = Book::create(
                id: BookId::generate(),
                title: 'テスト書籍',
            );

            expect($book->isAvailable())->toBeTrue();
        });

        it('borrowed 状態の場合は false を返すこと', function () {
            $book = Book::create(
                id: BookId::generate(),
                title: 'テスト書籍',
            );
            $book->borrow();

            expect($book->isAvailable())->toBeFalse();
        });

        it('reserved 状態の場合は false を返すこと', function () {
            $book = Book::create(
                id: BookId::generate(),
                title: 'テスト書籍',
            );
            $book->reserve();

            expect($book->isAvailable())->toBeFalse();
        });
    });

    describe('reconstruct', function () {
        it('永続化データから蔵書を復元できること', function () {
            $id = BookId::generate();
            $title = 'ドメイン駆動設計入門';
            $author = 'エリック・エヴァンス';
            $isbn = ISBN::fromString('978-4-7981-2196-3');
            $publisher = '翔泳社';
            $publishedYear = 2011;
            $genre = '技術書';
            $status = BookStatus::borrowed();

            $book = Book::reconstruct(
                id: $id,
                title: $title,
                author: $author,
                isbn: $isbn,
                publisher: $publisher,
                publishedYear: $publishedYear,
                genre: $genre,
                status: $status,
            );

            expect($book->id()->equals($id))->toBeTrue();
            expect($book->title())->toBe($title);
            expect($book->author())->toBe($author);
            expect($book->isbn()->equals($isbn))->toBeTrue();
            expect($book->publisher())->toBe($publisher);
            expect($book->publishedYear())->toBe($publishedYear);
            expect($book->genre())->toBe($genre);
            expect($book->status()->isBorrowed())->toBeTrue();
        });
    });

    describe('status transitions', function () {
        describe('borrow', function () {
            it('available 状態から borrowed 状態へ遷移できること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                $book->borrow();

                expect($book->status()->isBorrowed())->toBeTrue();
            });

            it('borrowed 状態から borrow は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->borrow();

                expect(fn () => $book->borrow())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });

            it('reserved 状態から borrow は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->reserve();

                expect(fn () => $book->borrow())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });
        });

        describe('return', function () {
            it('borrowed 状態から available 状態へ遷移できること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->borrow();

                $book->return();

                expect($book->status()->isAvailable())->toBeTrue();
            });

            it('available 状態から return は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                expect(fn () => $book->return())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });

            it('reserved 状態から return は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->reserve();

                expect(fn () => $book->return())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });
        });

        describe('reserve', function () {
            it('available 状態から reserved 状態へ遷移できること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                $book->reserve();

                expect($book->status()->isReserved())->toBeTrue();
            });

            it('borrowed 状態から reserve は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->borrow();

                expect(fn () => $book->reserve())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });

            it('reserved 状態から reserve は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->reserve();

                expect(fn () => $book->reserve())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });
        });

        describe('lendToReserver', function () {
            it('reserved 状態から borrowed 状態へ遷移できること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->reserve();

                $book->lendToReserver();

                expect($book->status()->isBorrowed())->toBeTrue();
            });

            it('available 状態から lendToReserver は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                expect(fn () => $book->lendToReserver())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });

            it('borrowed 状態から lendToReserver は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->borrow();

                expect(fn () => $book->lendToReserver())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });
        });

        describe('cancelReservation', function () {
            it('reserved 状態から available 状態へ遷移できること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->reserve();

                $book->cancelReservation();

                expect($book->status()->isAvailable())->toBeTrue();
            });

            it('available 状態から cancelReservation は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                expect(fn () => $book->cancelReservation())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });

            it('borrowed 状態から cancelReservation は例外がスローされること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );
                $book->borrow();

                expect(fn () => $book->cancelReservation())
                    ->toThrow(\Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException::class);
            });
        });

        describe('complete workflow', function () {
            it('貸出→返却の完全なワークフローが動作すること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                expect($book->isAvailable())->toBeTrue();

                $book->borrow();
                expect($book->status()->isBorrowed())->toBeTrue();

                $book->return();
                expect($book->isAvailable())->toBeTrue();
            });

            it('予約→予約者への貸出→返却の完全なワークフローが動作すること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                expect($book->isAvailable())->toBeTrue();

                $book->reserve();
                expect($book->status()->isReserved())->toBeTrue();

                $book->lendToReserver();
                expect($book->status()->isBorrowed())->toBeTrue();

                $book->return();
                expect($book->isAvailable())->toBeTrue();
            });

            it('予約→キャンセルのワークフローが動作すること', function () {
                $book = Book::create(
                    id: BookId::generate(),
                    title: 'テスト書籍',
                );

                $book->reserve();
                expect($book->status()->isReserved())->toBeTrue();

                $book->cancelReservation();
                expect($book->isAvailable())->toBeTrue();
            });
        });
    });
});
