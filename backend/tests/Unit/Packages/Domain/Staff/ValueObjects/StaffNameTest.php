<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\ValueObjects;

use Packages\Domain\Staff\Domain\Exceptions\InvalidStaffNameException;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;

/**
 * StaffName Value Object のテスト
 */
describe('StaffName', function () {
    describe('create', function () {
        it('有効な職員名を作成できること', function () {
            $name = StaffName::create('山田太郎');

            expect($name)->toBeInstanceOf(StaffName::class);
            expect($name->value())->toBe('山田太郎');
        });

        it('前後の空白を除去すること', function () {
            $name = StaffName::create('  山田太郎  ');

            expect($name->value())->toBe('山田太郎');
        });

        it('制御文字を除去すること', function () {
            $name = StaffName::create("山田\x00太郎\x1F");

            expect($name->value())->toBe('山田太郎');
        });

        it('英数字を含む名前を作成できること', function () {
            $name = StaffName::create('John Smith 123');

            expect($name->value())->toBe('John Smith 123');
        });

        it('100文字の職員名を受け入れること', function () {
            $longName = str_repeat('あ', 100);
            $name = StaffName::create($longName);

            expect($name->value())->toBe($longName);
        });
    });

    describe('validation', function () {
        it('空文字列の場合は例外がスローされること', function () {
            expect(fn () => StaffName::create(''))
                ->toThrow(InvalidStaffNameException::class, '職員名を入力してください');
        });

        it('空白のみの場合は例外がスローされること', function () {
            expect(fn () => StaffName::create('   '))
                ->toThrow(InvalidStaffNameException::class, '職員名を入力してください');
        });

        it('制御文字のみの場合は例外がスローされること', function () {
            expect(fn () => StaffName::create("\x00\x1F"))
                ->toThrow(InvalidStaffNameException::class, '職員名を入力してください');
        });

        it('101文字以上の場合は例外がスローされること', function () {
            $longName = str_repeat('あ', 101);
            expect(fn () => StaffName::create($longName))
                ->toThrow(InvalidStaffNameException::class, '職員名は100文字以下で入力してください');
        });
    });

    describe('fromString', function () {
        it('文字列から StaffName を復元できること', function () {
            $name = StaffName::fromString('山田太郎');

            expect($name)->toBeInstanceOf(StaffName::class);
            expect($name->value())->toBe('山田太郎');
        });
    });

    describe('value', function () {
        it('内部値を取得できること', function () {
            $name = StaffName::create('山田太郎');

            expect($name->value())->toBe('山田太郎');
        });
    });

    describe('equals', function () {
        it('同じ値を持つ StaffName は等しいこと', function () {
            $name1 = StaffName::create('山田太郎');
            $name2 = StaffName::create('山田太郎');

            expect($name1->equals($name2))->toBeTrue();
        });

        it('異なる値を持つ StaffName は等しくないこと', function () {
            $name1 = StaffName::create('山田太郎');
            $name2 = StaffName::create('鈴木花子');

            expect($name1->equals($name2))->toBeFalse();
        });
    });

    describe('__toString', function () {
        it('文字列に変換できること', function () {
            $name = StaffName::create('山田太郎');

            expect((string) $name)->toBe('山田太郎');
        });
    });
});
