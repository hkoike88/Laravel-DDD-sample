<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\ValueObjects;

use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * StaffId Value Object のテスト
 */
describe('StaffId', function () {
    describe('generate', function () {
        it('新規 ULID を生成できること', function () {
            $staffId = StaffId::generate();

            expect($staffId)->toBeInstanceOf(StaffId::class);
            expect($staffId->value())->toBeString();
            expect(strlen($staffId->value()))->toBe(26);
        });

        it('生成される ULID はユニークであること', function () {
            $id1 = StaffId::generate();
            $id2 = StaffId::generate();

            expect($id1->value())->not->toBe($id2->value());
            expect($id1->equals($id2))->toBeFalse();
        });
    });

    describe('fromString', function () {
        it('有効な ULID 文字列から生成できること', function () {
            $value = '01HQXGZG3QZJXVWB5XPNKXYZ01';
            $staffId = StaffId::fromString($value);

            expect($staffId)->toBeInstanceOf(StaffId::class);
            expect($staffId->value())->toBe($value);
        });

        it('生成した ULID を文字列から復元できること', function () {
            $original = StaffId::generate();
            $restored = StaffId::fromString($original->value());

            expect($restored->value())->toBe($original->value());
            expect($restored->equals($original))->toBeTrue();
        });
    });

    describe('value', function () {
        it('内部値を取得できること', function () {
            $staffId = StaffId::generate();
            $value = $staffId->value();

            expect($value)->toBeString();
            expect(strlen($value))->toBe(26);
        });
    });

    describe('equals', function () {
        it('同じ値を持つ StaffId は等しいこと', function () {
            $value = '01HQXGZG3QZJXVWB5XPNKXYZ01';
            $id1 = StaffId::fromString($value);
            $id2 = StaffId::fromString($value);

            expect($id1->equals($id2))->toBeTrue();
        });

        it('異なる値を持つ StaffId は等しくないこと', function () {
            $id1 = StaffId::generate();
            $id2 = StaffId::generate();

            expect($id1->equals($id2))->toBeFalse();
        });
    });

    describe('__toString', function () {
        it('文字列に変換できること', function () {
            $value = '01HQXGZG3QZJXVWB5XPNKXYZ01';
            $staffId = StaffId::fromString($value);

            expect((string) $staffId)->toBe($value);
        });
    });
});
