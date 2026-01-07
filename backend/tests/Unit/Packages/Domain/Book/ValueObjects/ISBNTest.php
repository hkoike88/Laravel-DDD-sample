<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\ValueObjects;

use Packages\Domain\Book\Domain\Exceptions\InvalidISBNException;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

/**
 * ISBN Value Object のテスト
 */
describe('ISBN', function () {
    describe('fromString (ISBN-13)', function () {
        it('有効な ISBN-13（ハイフンあり）を作成できること', function () {
            $isbn = ISBN::fromString('978-4-7981-2196-3');

            expect($isbn)->toBeInstanceOf(ISBN::class);
            expect($isbn->value())->toBe('9784798121963');
            expect($isbn->isISBN13())->toBeTrue();
            expect($isbn->isISBN10())->toBeFalse();
        });

        it('有効な ISBN-13（ハイフンなし）を作成できること', function () {
            $isbn = ISBN::fromString('9784798121963');

            expect($isbn)->toBeInstanceOf(ISBN::class);
            expect($isbn->value())->toBe('9784798121963');
            expect($isbn->isISBN13())->toBeTrue();
        });

        it('978 プレフィックスの ISBN-13 を受け入れること', function () {
            $isbn = ISBN::fromString('978-0-13-468599-1');

            expect($isbn->value())->toBe('9780134685991');
            expect($isbn->isISBN13())->toBeTrue();
        });

        it('979 プレフィックスの ISBN-13 を受け入れること', function () {
            $isbn = ISBN::fromString('979-10-90636-07-1');

            expect($isbn->value())->toBe('9791090636071');
            expect($isbn->isISBN13())->toBeTrue();
        });

        it('不正なプレフィックスの ISBN-13 は拒否されること', function () {
            expect(fn () => ISBN::fromString('977-0-13-468599-2'))
                ->toThrow(InvalidISBNException::class);
        });

        it('チェックディジットが不正な ISBN-13 は拒否されること', function () {
            expect(fn () => ISBN::fromString('978-4-7981-2196-0'))
                ->toThrow(InvalidISBNException::class);
        });
    });

    describe('fromString (ISBN-10)', function () {
        it('有効な ISBN-10（ハイフンあり）を作成できること', function () {
            $isbn = ISBN::fromString('1-5586-0832-X');

            expect($isbn)->toBeInstanceOf(ISBN::class);
            expect($isbn->value())->toBe('155860832X');
            expect($isbn->isISBN10())->toBeTrue();
            expect($isbn->isISBN13())->toBeFalse();
        });

        it('有効な ISBN-10（ハイフンなし）を作成できること', function () {
            $isbn = ISBN::fromString('155860832X');

            expect($isbn)->toBeInstanceOf(ISBN::class);
            expect($isbn->value())->toBe('155860832X');
            expect($isbn->isISBN10())->toBeTrue();
        });

        it('小文字の X を大文字に正規化すること', function () {
            $isbn = ISBN::fromString('155860832x');

            expect($isbn->value())->toBe('155860832X');
        });

        it('チェックディジットが数字の ISBN-10 を受け入れること', function () {
            $isbn = ISBN::fromString('0-306-40615-2');

            expect($isbn->value())->toBe('0306406152');
            expect($isbn->isISBN10())->toBeTrue();
        });

        it('チェックディジットが不正な ISBN-10 は拒否されること', function () {
            expect(fn () => ISBN::fromString('4-7981-2196-1'))
                ->toThrow(InvalidISBNException::class);
        });
    });

    describe('fromString (invalid)', function () {
        it('桁数が不足している場合は例外がスローされること', function () {
            expect(fn () => ISBN::fromString('123'))
                ->toThrow(InvalidISBNException::class);
        });

        it('桁数が超過している場合は例外がスローされること', function () {
            expect(fn () => ISBN::fromString('12345678901234'))
                ->toThrow(InvalidISBNException::class);
        });

        it('数字以外の文字が含まれる場合は例外がスローされること', function () {
            expect(fn () => ISBN::fromString('978-4-7981-ABCD-3'))
                ->toThrow(InvalidISBNException::class);
        });

        it('空文字の場合は例外がスローされること', function () {
            expect(fn () => ISBN::fromString(''))
                ->toThrow(InvalidISBNException::class);
        });
    });

    describe('formatted', function () {
        it('ISBN-13 をハイフン付きでフォーマットできること', function () {
            $isbn = ISBN::fromString('9784798121963');

            expect($isbn->formatted())->toBe('978-4-7981-2196-3');
        });

        it('ISBN-10 をハイフン付きでフォーマットできること', function () {
            $isbn = ISBN::fromString('155860832X');

            expect($isbn->formatted())->toBe('1-5586-0832-X');
        });
    });

    describe('equals', function () {
        it('同じ値を持つ ISBN は等しいこと', function () {
            $isbn1 = ISBN::fromString('978-4-7981-2196-3');
            $isbn2 = ISBN::fromString('9784798121963');

            expect($isbn1->equals($isbn2))->toBeTrue();
        });

        it('異なる値を持つ ISBN は等しくないこと', function () {
            $isbn1 = ISBN::fromString('978-4-7981-2196-3');
            $isbn2 = ISBN::fromString('978-0-13-468599-1');

            expect($isbn1->equals($isbn2))->toBeFalse();
        });
    });

    describe('getInvalidValue', function () {
        it('例外から不正な入力値を取得できること', function () {
            try {
                ISBN::fromString('invalid-isbn');
            } catch (InvalidISBNException $e) {
                expect($e->getInvalidValue())->toBe('invalid-isbn');
                expect($e->getReason())->toBe(InvalidISBNException::REASON_INVALID_FORMAT);
            }
        });

        it('チェックサムエラーの理由を取得できること', function () {
            try {
                ISBN::fromString('978-4-7981-2196-0');
            } catch (InvalidISBNException $e) {
                expect($e->getReason())->toBe(InvalidISBNException::REASON_INVALID_CHECKSUM);
            }
        });
    });
});
