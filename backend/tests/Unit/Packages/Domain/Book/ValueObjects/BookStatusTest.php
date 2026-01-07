<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book\ValueObjects;

use InvalidArgumentException;
use Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;

/**
 * BookStatus Value Object のテスト
 */
describe('BookStatus', function () {
    describe('factory methods', function () {
        it('available 状態を生成できること', function () {
            $status = BookStatus::available();

            expect($status)->toBeInstanceOf(BookStatus::class);
            expect($status->value())->toBe('available');
            expect($status->isAvailable())->toBeTrue();
        });

        it('borrowed 状態を生成できること', function () {
            $status = BookStatus::borrowed();

            expect($status)->toBeInstanceOf(BookStatus::class);
            expect($status->value())->toBe('borrowed');
            expect($status->isBorrowed())->toBeTrue();
        });

        it('reserved 状態を生成できること', function () {
            $status = BookStatus::reserved();

            expect($status)->toBeInstanceOf(BookStatus::class);
            expect($status->value())->toBe('reserved');
            expect($status->isReserved())->toBeTrue();
        });
    });

    describe('from', function () {
        it('有効な文字列から生成できること', function () {
            expect(BookStatus::from('available')->isAvailable())->toBeTrue();
            expect(BookStatus::from('borrowed')->isBorrowed())->toBeTrue();
            expect(BookStatus::from('reserved')->isReserved())->toBeTrue();
        });

        it('無効な文字列の場合は例外がスローされること', function () {
            expect(fn () => BookStatus::from('invalid'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('state checks', function () {
        it('available 状態の判定が正しいこと', function () {
            $status = BookStatus::available();

            expect($status->isAvailable())->toBeTrue();
            expect($status->isBorrowed())->toBeFalse();
            expect($status->isReserved())->toBeFalse();
        });

        it('borrowed 状態の判定が正しいこと', function () {
            $status = BookStatus::borrowed();

            expect($status->isAvailable())->toBeFalse();
            expect($status->isBorrowed())->toBeTrue();
            expect($status->isReserved())->toBeFalse();
        });

        it('reserved 状態の判定が正しいこと', function () {
            $status = BookStatus::reserved();

            expect($status->isAvailable())->toBeFalse();
            expect($status->isBorrowed())->toBeFalse();
            expect($status->isReserved())->toBeTrue();
        });
    });

    describe('can* methods', function () {
        describe('available 状態から', function () {
            it('貸出可能であること', function () {
                $status = BookStatus::available();
                expect($status->canBorrow())->toBeTrue();
            });

            it('返却不可であること', function () {
                $status = BookStatus::available();
                expect($status->canReturn())->toBeFalse();
            });

            it('予約可能であること', function () {
                $status = BookStatus::available();
                expect($status->canReserve())->toBeTrue();
            });

            it('予約者への貸出不可であること', function () {
                $status = BookStatus::available();
                expect($status->canLendToReserver())->toBeFalse();
            });

            it('予約キャンセル不可であること', function () {
                $status = BookStatus::available();
                expect($status->canCancelReservation())->toBeFalse();
            });
        });

        describe('borrowed 状態から', function () {
            it('貸出不可であること', function () {
                $status = BookStatus::borrowed();
                expect($status->canBorrow())->toBeFalse();
            });

            it('返却可能であること', function () {
                $status = BookStatus::borrowed();
                expect($status->canReturn())->toBeTrue();
            });

            it('予約不可であること', function () {
                $status = BookStatus::borrowed();
                expect($status->canReserve())->toBeFalse();
            });
        });

        describe('reserved 状態から', function () {
            it('貸出不可であること', function () {
                $status = BookStatus::reserved();
                expect($status->canBorrow())->toBeFalse();
            });

            it('返却不可であること', function () {
                $status = BookStatus::reserved();
                expect($status->canReturn())->toBeFalse();
            });

            it('予約不可であること', function () {
                $status = BookStatus::reserved();
                expect($status->canReserve())->toBeFalse();
            });

            it('予約者への貸出可能であること', function () {
                $status = BookStatus::reserved();
                expect($status->canLendToReserver())->toBeTrue();
            });

            it('予約キャンセル可能であること', function () {
                $status = BookStatus::reserved();
                expect($status->canCancelReservation())->toBeTrue();
            });
        });
    });

    describe('state transitions', function () {
        describe('available から', function () {
            it('borrowed へ遷移できること', function () {
                $status = BookStatus::available();
                $newStatus = $status->toBorrowed();

                expect($newStatus->isBorrowed())->toBeTrue();
            });

            it('reserved へ遷移できること', function () {
                $status = BookStatus::available();
                $newStatus = $status->toReserved();

                expect($newStatus->isReserved())->toBeTrue();
            });

            it('return は不可（例外がスローされること）', function () {
                $status = BookStatus::available();

                expect(fn () => $status->toAvailableByReturn())
                    ->toThrow(InvalidBookStatusTransitionException::class);
            });
        });

        describe('borrowed から', function () {
            it('available へ遷移（返却）できること', function () {
                $status = BookStatus::borrowed();
                $newStatus = $status->toAvailableByReturn();

                expect($newStatus->isAvailable())->toBeTrue();
            });

            it('borrow は不可（例外がスローされること）', function () {
                $status = BookStatus::borrowed();

                expect(fn () => $status->toBorrowed())
                    ->toThrow(InvalidBookStatusTransitionException::class);
            });

            it('reserve は不可（例外がスローされること）', function () {
                $status = BookStatus::borrowed();

                expect(fn () => $status->toReserved())
                    ->toThrow(InvalidBookStatusTransitionException::class);
            });
        });

        describe('reserved から', function () {
            it('borrowed へ遷移（予約者への貸出）できること', function () {
                $status = BookStatus::reserved();
                $newStatus = $status->toBorrowedFromReserved();

                expect($newStatus->isBorrowed())->toBeTrue();
            });

            it('available へ遷移（予約キャンセル）できること', function () {
                $status = BookStatus::reserved();
                $newStatus = $status->toAvailableByCancellation();

                expect($newStatus->isAvailable())->toBeTrue();
            });

            it('borrow は不可（例外がスローされること）', function () {
                $status = BookStatus::reserved();

                expect(fn () => $status->toBorrowed())
                    ->toThrow(InvalidBookStatusTransitionException::class);
            });
        });
    });

    describe('exception details', function () {
        it('例外に遷移元・遷移先・操作名が含まれること', function () {
            try {
                BookStatus::available()->toAvailableByReturn();
            } catch (InvalidBookStatusTransitionException $e) {
                expect($e->getFrom())->toBe('available');
                expect($e->getTo())->toBe('available');
                expect($e->getAction())->toBe('return');
            }
        });
    });

    describe('equals', function () {
        it('同じ値を持つ BookStatus は等しいこと', function () {
            $status1 = BookStatus::available();
            $status2 = BookStatus::available();

            expect($status1->equals($status2))->toBeTrue();
        });

        it('異なる値を持つ BookStatus は等しくないこと', function () {
            $status1 = BookStatus::available();
            $status2 = BookStatus::borrowed();

            expect($status1->equals($status2))->toBeFalse();
        });
    });
});
