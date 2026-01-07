<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\ValueObjects;

use Packages\Domain\Staff\Domain\Exceptions\InvalidEmailException;
use Stringable;

/**
 * メールアドレス Value Object
 *
 * 職員の連絡先かつログイン識別子として使用されるメールアドレス。
 * 形式検証済みで小文字に正規化された値を保持する。
 */
final readonly class Email implements Stringable
{
    /**
     * 最大文字数
     */
    private const MAX_LENGTH = 255;

    /**
     * コンストラクタ
     *
     * @param  string  $value  メールアドレス（小文字正規化済み）
     */
    private function __construct(
        private string $value
    ) {}

    /**
     * メールアドレスを作成
     *
     * @param  string  $value  メールアドレス
     *
     * @throws InvalidEmailException メールアドレスが要件を満たさない場合
     */
    public static function create(string $value): self
    {
        // 小文字に正規化
        $normalized = mb_strtolower(trim($value));

        self::validate($normalized);

        return new self($normalized);
    }

    /**
     * メールアドレスのバリデーション
     *
     * @param  string  $value  正規化済みメールアドレス
     *
     * @throws InvalidEmailException メールアドレスが要件を満たさない場合
     */
    private static function validate(string $value): void
    {
        if ($value === '') {
            throw InvalidEmailException::empty();
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw InvalidEmailException::tooLong(self::MAX_LENGTH);
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidEmailException::invalidFormat();
        }
    }

    /**
     * 文字列から Email を生成（DBからの復元用）
     *
     * @param  string  $value  メールアドレス
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
     * @param  Email  $other  比較対象
     */
    public function equals(Email $other): bool
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
