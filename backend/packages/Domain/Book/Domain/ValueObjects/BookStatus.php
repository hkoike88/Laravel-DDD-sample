<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\ValueObjects;

use InvalidArgumentException;
use Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException;

/**
 * 蔵書ステータス Value Object
 *
 * 蔵書の現在の貸出状態を表す列挙型的 Value Object。
 * 状態遷移ルールを内包し、不正な遷移を防止する。
 *
 * 状態:
 * - available: 利用可能（貸出・予約可能）
 * - borrowed: 貸出中（返却のみ可能）
 * - reserved: 予約中（予約者への貸出またはキャンセル可能）
 */
final readonly class BookStatus
{
    /**
     * 利用可能状態
     */
    public const AVAILABLE = 'available';

    /**
     * 貸出中状態
     */
    public const BORROWED = 'borrowed';

    /**
     * 予約中状態
     */
    public const RESERVED = 'reserved';

    /**
     * 有効な状態値一覧
     */
    private const VALID_VALUES = [
        self::AVAILABLE,
        self::BORROWED,
        self::RESERVED,
    ];

    /**
     * コンストラクタ
     *
     * @param  string  $value  状態値
     */
    private function __construct(
        private string $value
    ) {}

    /**
     * Available 状態を生成
     */
    public static function available(): self
    {
        return new self(self::AVAILABLE);
    }

    /**
     * Borrowed 状態を生成
     */
    public static function borrowed(): self
    {
        return new self(self::BORROWED);
    }

    /**
     * Reserved 状態を生成
     */
    public static function reserved(): self
    {
        return new self(self::RESERVED);
    }

    /**
     * 文字列から生成
     *
     * @param  string  $value  状態値
     *
     * @throws InvalidArgumentException 無効な状態値の場合
     */
    public static function from(string $value): self
    {
        if (! in_array($value, self::VALID_VALUES, true)) {
            throw new InvalidArgumentException(
                "無効なステータス値です: {$value}"
            );
        }

        return new self($value);
    }

    /**
     * 内部値を取得
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * 貸出可能か判定
     */
    public function canBorrow(): bool
    {
        return $this->value === self::AVAILABLE;
    }

    /**
     * 返却可能か判定
     */
    public function canReturn(): bool
    {
        return $this->value === self::BORROWED;
    }

    /**
     * 予約可能か判定
     */
    public function canReserve(): bool
    {
        return $this->value === self::AVAILABLE;
    }

    /**
     * 予約者への貸出可能か判定
     */
    public function canLendToReserver(): bool
    {
        return $this->value === self::RESERVED;
    }

    /**
     * 予約キャンセル可能か判定
     */
    public function canCancelReservation(): bool
    {
        return $this->value === self::RESERVED;
    }

    /**
     * Available 状態か判定
     */
    public function isAvailable(): bool
    {
        return $this->value === self::AVAILABLE;
    }

    /**
     * Borrowed 状態か判定
     */
    public function isBorrowed(): bool
    {
        return $this->value === self::BORROWED;
    }

    /**
     * Reserved 状態か判定
     */
    public function isReserved(): bool
    {
        return $this->value === self::RESERVED;
    }

    /**
     * 貸出状態へ遷移
     *
     * @throws InvalidBookStatusTransitionException 遷移が許可されていない場合
     */
    public function toBorrowed(): self
    {
        if (! $this->canBorrow()) {
            throw new InvalidBookStatusTransitionException(
                $this->value,
                self::BORROWED,
                'borrow'
            );
        }

        return self::borrowed();
    }

    /**
     * 利用可能状態へ遷移（返却）
     *
     * @throws InvalidBookStatusTransitionException 遷移が許可されていない場合
     */
    public function toAvailableByReturn(): self
    {
        if (! $this->canReturn()) {
            throw new InvalidBookStatusTransitionException(
                $this->value,
                self::AVAILABLE,
                'return'
            );
        }

        return self::available();
    }

    /**
     * 予約状態へ遷移
     *
     * @throws InvalidBookStatusTransitionException 遷移が許可されていない場合
     */
    public function toReserved(): self
    {
        if (! $this->canReserve()) {
            throw new InvalidBookStatusTransitionException(
                $this->value,
                self::RESERVED,
                'reserve'
            );
        }

        return self::reserved();
    }

    /**
     * 貸出状態へ遷移（予約者への貸出）
     *
     * @throws InvalidBookStatusTransitionException 遷移が許可されていない場合
     */
    public function toBorrowedFromReserved(): self
    {
        if (! $this->canLendToReserver()) {
            throw new InvalidBookStatusTransitionException(
                $this->value,
                self::BORROWED,
                'lendToReserver'
            );
        }

        return self::borrowed();
    }

    /**
     * 利用可能状態へ遷移（予約キャンセル）
     *
     * @throws InvalidBookStatusTransitionException 遷移が許可されていない場合
     */
    public function toAvailableByCancellation(): self
    {
        if (! $this->canCancelReservation()) {
            throw new InvalidBookStatusTransitionException(
                $this->value,
                self::AVAILABLE,
                'cancelReservation'
            );
        }

        return self::available();
    }

    /**
     * 等価性判定
     *
     * @param  BookStatus  $other  比較対象
     */
    public function equals(BookStatus $other): bool
    {
        return $this->value === $other->value;
    }
}
