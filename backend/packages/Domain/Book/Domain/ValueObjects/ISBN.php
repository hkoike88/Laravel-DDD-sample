<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\ValueObjects;

use Packages\Domain\Book\Domain\Exceptions\InvalidISBNException;

/**
 * ISBN Value Object
 *
 * 国際標準図書番号。ISBN-10 または ISBN-13 形式をサポート。
 * 内部的にはハイフンなしの正規化された値を保持。
 */
final readonly class ISBN
{
    /**
     * ISBN-10 タイプ
     */
    private const TYPE_ISBN10 = 'ISBN-10';

    /**
     * ISBN-13 タイプ
     */
    private const TYPE_ISBN13 = 'ISBN-13';

    /**
     * コンストラクタ
     *
     * @param  string  $value  正規化された ISBN（ハイフンなし）
     * @param  string  $type  ISBN タイプ（ISBN-10 または ISBN-13）
     */
    private function __construct(
        private string $value,
        private string $type
    ) {}

    /**
     * 文字列から ISBN を生成
     *
     * @param  string  $value  ISBN 文字列（ハイフンあり/なし両対応）
     *
     * @throws InvalidISBNException 形式またはチェックディジットが不正な場合
     */
    public static function fromString(string $value): self
    {
        // ハイフンと空白を除去して正規化
        $normalized = preg_replace('/[\s-]/', '', $value);

        if ($normalized === null) {
            throw InvalidISBNException::invalidFormat($value);
        }

        $length = strlen($normalized);

        // 桁数に応じて ISBN-10 または ISBN-13 として処理
        if ($length === 13) {
            return self::createISBN13($normalized, $value);
        }

        if ($length === 10) {
            return self::createISBN10($normalized, $value);
        }

        throw InvalidISBNException::invalidFormat($value);
    }

    /**
     * ISBN-13 を生成
     *
     * @param  string  $normalized  正規化された値
     * @param  string  $original  元の入力値
     *
     * @throws InvalidISBNException 形式またはチェックサムが不正な場合
     */
    private static function createISBN13(string $normalized, string $original): self
    {
        // 数字のみであることを確認
        if (! ctype_digit($normalized)) {
            throw InvalidISBNException::invalidFormat($original);
        }

        // 先頭3桁が 978 または 979 であることを確認
        $prefix = substr($normalized, 0, 3);
        if ($prefix !== '978' && $prefix !== '979') {
            throw InvalidISBNException::invalidFormat($original);
        }

        // チェックディジット検証
        if (! self::validateISBN13Checksum($normalized)) {
            throw InvalidISBNException::invalidChecksum($original);
        }

        return new self($normalized, self::TYPE_ISBN13);
    }

    /**
     * ISBN-10 を生成
     *
     * @param  string  $normalized  正規化された値
     * @param  string  $original  元の入力値
     *
     * @throws InvalidISBNException 形式またはチェックサムが不正な場合
     */
    private static function createISBN10(string $normalized, string $original): self
    {
        // 先頭9桁が数字であることを確認
        $firstNine = substr($normalized, 0, 9);
        if (! ctype_digit($firstNine)) {
            throw InvalidISBNException::invalidFormat($original);
        }

        // 最後の桁が数字または X であることを確認
        $lastChar = strtoupper(substr($normalized, 9, 1));
        if (! ctype_digit($lastChar) && $lastChar !== 'X') {
            throw InvalidISBNException::invalidFormat($original);
        }

        // 正規化（X を大文字に）
        $normalized = $firstNine.$lastChar;

        // チェックディジット検証
        if (! self::validateISBN10Checksum($normalized)) {
            throw InvalidISBNException::invalidChecksum($original);
        }

        return new self($normalized, self::TYPE_ISBN10);
    }

    /**
     * ISBN-13 のチェックサムを検証
     *
     * @param  string  $isbn  正規化された ISBN-13
     */
    private static function validateISBN13Checksum(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $isbn[$i];
            // 奇数位置（1, 3, 5, ...）は1倍、偶数位置（2, 4, 6, ...）は3倍
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $digit * $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $isbn[12];
    }

    /**
     * ISBN-10 のチェックサムを検証
     *
     * ISBN-10 の検証: 各桁に 10, 9, 8, ..., 1 の重みを掛けて合計し、
     * 11 で割った余りが 0 なら有効
     *
     * @param  string  $isbn  正規化された ISBN-10
     */
    private static function validateISBN10Checksum(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $char = $isbn[$i];
            // X は 10 として扱う
            $digit = ($char === 'X') ? 10 : (int) $char;
            // 各桁に 10, 9, 8, ..., 1 を掛ける
            $weight = 10 - $i;
            $sum += $digit * $weight;
        }

        return $sum % 11 === 0;
    }

    /**
     * 正規化された値を取得（ハイフンなし）
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * ハイフン付きフォーマットを取得
     */
    public function formatted(): string
    {
        if ($this->type === self::TYPE_ISBN13) {
            return $this->formatISBN13();
        }

        return $this->formatISBN10();
    }

    /**
     * ISBN-13 をフォーマット
     */
    private function formatISBN13(): string
    {
        // 標準的な ISBN-13 フォーマット: 978-X-XXXX-XXXX-X
        // 簡易的に 978-1-2345-6789-0 形式で返す
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($this->value, 0, 3),
            substr($this->value, 3, 1),
            substr($this->value, 4, 4),
            substr($this->value, 8, 4),
            substr($this->value, 12, 1)
        );
    }

    /**
     * ISBN-10 をフォーマット
     */
    private function formatISBN10(): string
    {
        // 標準的な ISBN-10 フォーマット: X-XXXX-XXXX-X
        return sprintf(
            '%s-%s-%s-%s',
            substr($this->value, 0, 1),
            substr($this->value, 1, 4),
            substr($this->value, 5, 4),
            substr($this->value, 9, 1)
        );
    }

    /**
     * ISBN-13 か判定
     */
    public function isISBN13(): bool
    {
        return $this->type === self::TYPE_ISBN13;
    }

    /**
     * ISBN-10 か判定
     */
    public function isISBN10(): bool
    {
        return $this->type === self::TYPE_ISBN10;
    }

    /**
     * 等価性判定
     *
     * @param  ISBN  $other  比較対象
     */
    public function equals(ISBN $other): bool
    {
        return $this->value === $other->value;
    }
}
