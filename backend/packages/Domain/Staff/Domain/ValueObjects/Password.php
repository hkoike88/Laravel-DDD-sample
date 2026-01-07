<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\ValueObjects;

use Illuminate\Support\Facades\Hash;
use Packages\Domain\Staff\Domain\Exceptions\InvalidPasswordException;

/**
 * パスワード Value Object
 *
 * 認証に使用されるハッシュ化された秘密情報。
 * ハッシュ化済みの状態を保持し、平文パスワードは保持しない。
 */
final readonly class Password
{
    /**
     * 最小文字数
     */
    private const MIN_LENGTH = 8;

    /**
     * 最大文字数（bcrypt の制限）
     */
    private const MAX_LENGTH = 72;

    /**
     * コンストラクタ
     *
     * @param  string  $hashedValue  ハッシュ化されたパスワード
     */
    private function __construct(
        private string $hashedValue
    ) {}

    /**
     * 平文パスワードからハッシュ化して生成
     *
     * @param  string  $plainText  平文パスワード
     *
     * @throws InvalidPasswordException パスワードが要件を満たさない場合
     */
    public static function fromPlainText(string $plainText): self
    {
        self::validate($plainText);

        $hashed = Hash::make($plainText);

        return new self($hashed);
    }

    /**
     * パスワードのバリデーション
     *
     * @param  string  $plainText  平文パスワード
     *
     * @throws InvalidPasswordException パスワードが要件を満たさない場合
     */
    private static function validate(string $plainText): void
    {
        $length = mb_strlen($plainText);

        if ($length === 0) {
            throw InvalidPasswordException::empty();
        }

        if ($length < self::MIN_LENGTH) {
            throw InvalidPasswordException::tooShort(self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            throw InvalidPasswordException::tooLong(self::MAX_LENGTH);
        }
    }

    /**
     * ハッシュ値から復元（DBからの復元用）
     *
     * @param  string  $hash  ハッシュ化されたパスワード
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    /**
     * 平文パスワードを検証
     *
     * @param  string  $plainText  平文パスワード
     * @return bool 一致する場合 true
     */
    public function verify(string $plainText): bool
    {
        return Hash::check($plainText, $this->hashedValue);
    }

    /**
     * ハッシュ値を取得
     */
    public function hashedValue(): string
    {
        return $this->hashedValue;
    }
}
