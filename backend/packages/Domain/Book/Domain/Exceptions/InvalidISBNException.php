<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Exceptions;

use DomainException;

/**
 * ISBN の形式またはチェックディジットが不正な場合にスローされる例外
 *
 * ISBN-10 または ISBN-13 の形式に準拠していない、
 * またはチェックディジットが一致しない場合にスローされる。
 */
final class InvalidISBNException extends DomainException
{
    /**
     * エラー理由: 形式不正
     */
    public const REASON_INVALID_FORMAT = 'invalid_format';

    /**
     * エラー理由: チェックサム不正
     */
    public const REASON_INVALID_CHECKSUM = 'invalid_checksum';

    /**
     * 不正な入力値
     */
    private string $invalidValue;

    /**
     * エラー理由
     */
    private string $reason;

    /**
     * コンストラクタ
     *
     * @param  string  $invalidValue  不正な入力値
     * @param  string  $reason  エラー理由（invalid_format または invalid_checksum）
     */
    private function __construct(string $invalidValue, string $reason)
    {
        $this->invalidValue = $invalidValue;
        $this->reason = $reason;

        $message = match ($reason) {
            self::REASON_INVALID_FORMAT => "不正なISBN形式です: {$invalidValue}",
            self::REASON_INVALID_CHECKSUM => "ISBNのチェックディジットが不正です: {$invalidValue}",
            default => "不正なISBNです: {$invalidValue}",
        };

        parent::__construct($message);
    }

    /**
     * 形式不正の例外を生成
     *
     * @param  string  $value  不正な入力値
     */
    public static function invalidFormat(string $value): self
    {
        return new self($value, self::REASON_INVALID_FORMAT);
    }

    /**
     * チェックサム不正の例外を生成
     *
     * @param  string  $value  不正な入力値
     */
    public static function invalidChecksum(string $value): self
    {
        return new self($value, self::REASON_INVALID_CHECKSUM);
    }

    /**
     * 不正な入力値を取得
     */
    public function getInvalidValue(): string
    {
        return $this->invalidValue;
    }

    /**
     * エラー理由を取得
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
