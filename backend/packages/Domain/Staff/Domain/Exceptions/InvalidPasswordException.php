<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 無効なパスワード例外
 *
 * パスワードが要件を満たさない場合にスローされる例外。
 */
class InvalidPasswordException extends StaffDomainException
{
    /**
     * パスワードが短すぎる場合のエラー
     *
     * @param  int  $minLength  最小文字数
     */
    public static function tooShort(int $minLength): self
    {
        return new self(
            sprintf('パスワードは%d文字以上で入力してください', $minLength)
        );
    }

    /**
     * パスワードが長すぎる場合のエラー
     *
     * @param  int  $maxLength  最大文字数
     */
    public static function tooLong(int $maxLength): self
    {
        return new self(
            sprintf('パスワードは%d文字以下で入力してください', $maxLength)
        );
    }

    /**
     * パスワードが空の場合のエラー
     */
    public static function empty(): self
    {
        return new self('パスワードを入力してください');
    }
}
