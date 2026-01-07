<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\ValueObjects;

use Packages\Domain\Book\Domain\ValueObjects\BookId;

/**
 * BookId Value Object のテスト
 */
describe('BookId', function () {
    describe('generate', function () {
        it('新規 ULID を生成できること', function () {
            $bookId = BookId::generate();

            expect($bookId)->toBeInstanceOf(BookId::class);
            expect($bookId->value())->toBeString();
            expect(strlen($bookId->value()))->toBe(26);
        });

        it('生成される ULID はユニークであること', function () {
            $id1 = BookId::generate();
            $id2 = BookId::generate();

            expect($id1->value())->not->toBe($id2->value());
            expect($id1->equals($id2))->toBeFalse();
        });
    });

    describe('fromString', function () {
        it('有効な ULID 文字列から生成できること', function () {
            $value = '01HQXGZG3QZJXVWB5XPNKXYZ01';
            $bookId = BookId::fromString($value);

            expect($bookId)->toBeInstanceOf(BookId::class);
            expect($bookId->value())->toBe($value);
        });

        it('生成した ULID を文字列から復元できること', function () {
            $original = BookId::generate();
            $restored = BookId::fromString($original->value());

            expect($restored->value())->toBe($original->value());
            expect($restored->equals($original))->toBeTrue();
        });
    });

    describe('value', function () {
        it('内部値を取得できること', function () {
            $bookId = BookId::generate();
            $value = $bookId->value();

            expect($value)->toBeString();
            expect(strlen($value))->toBe(26);
        });
    });

    describe('equals', function () {
        it('同じ値を持つ BookId は等しいこと', function () {
            $value = '01HQXGZG3QZJXVWB5XPNKXYZ01';
            $id1 = BookId::fromString($value);
            $id2 = BookId::fromString($value);

            expect($id1->equals($id2))->toBeTrue();
        });

        it('異なる値を持つ BookId は等しくないこと', function () {
            $id1 = BookId::generate();
            $id2 = BookId::generate();

            expect($id1->equals($id2))->toBeFalse();
        });
    });

    describe('__toString', function () {
        it('文字列に変換できること', function () {
            $value = '01HQXGZG3QZJXVWB5XPNKXYZ01';
            $bookId = BookId::fromString($value);

            expect((string) $bookId)->toBe($value);
        });
    });
});
