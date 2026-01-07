<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\ValueObjects;

use Stringable;
use Symfony\Component\Uid\Ulid;

/**
 * 蔵書ID Value Object
 *
 * 蔵書を一意に識別する識別子。ULID 形式（26文字固定長）。
 * Crockford's Base32 エンコーディングを使用。
 */
final readonly class BookId implements Stringable
{
    /**
     * コンストラクタ
     *
     * @param  string  $value  ULID 文字列（26文字）
     */
    private function __construct(
        private string $value
    ) {}

    /**
     * 新規 ULID を生成
     */
    public static function generate(): self
    {
        $ulid = new Ulid;

        return new self($ulid->toBase32());
    }

    /**
     * 文字列から BookId を生成
     *
     * @param  string  $value  ULID 文字列
     */
    public static function fromString(string $value): self
    {
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
     * 等価性判定
     *
     * @param  BookId  $other  比較対象
     */
    public function equals(BookId $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * 文字列に変換
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
