<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\ValueObjects;

use Packages\Domain\Staff\Domain\Exceptions\InvalidStaffNameException;
use Stringable;

/**
 * 職員名 Value Object
 *
 * 職員の表示名を表す値オブジェクト。
 */
final readonly class StaffName implements Stringable
{
    /**
     * 最大文字数
     */
    private const MAX_LENGTH = 100;

    /**
     * コンストラクタ
     *
     * @param  string  $value  職員名
     */
    private function __construct(
        private string $value
    ) {}

    /**
     * 職員名を作成
     *
     * @param  string  $value  職員名
     *
     * @throws InvalidStaffNameException 職員名が要件を満たさない場合
     */
    public static function create(string $value): self
    {
        // 制御文字を除去
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $sanitized = trim($sanitized ?? '');

        self::validate($sanitized);

        return new self($sanitized);
    }

    /**
     * 職員名のバリデーション
     *
     * @param  string  $value  サニタイズ済み職員名
     *
     * @throws InvalidStaffNameException 職員名が要件を満たさない場合
     */
    private static function validate(string $value): void
    {
        if ($value === '') {
            throw InvalidStaffNameException::empty();
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw InvalidStaffNameException::tooLong(self::MAX_LENGTH);
        }
    }

    /**
     * 文字列から StaffName を生成（DBからの復元用）
     *
     * @param  string  $value  職員名
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
     * @param  StaffName  $other  比較対象
     */
    public function equals(StaffName $other): bool
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
