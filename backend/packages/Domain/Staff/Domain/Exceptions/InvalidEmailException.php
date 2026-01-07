<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 無効なメールアドレス例外
 *
 * メールアドレスが要件を満たさない場合にスローされる例外。
 */
class InvalidEmailException extends StaffDomainException
{
    /**
     * メールアドレスの形式が不正な場合のエラー
     */
    public static function invalidFormat(): self
    {
        return new self('メールアドレスの形式が正しくありません');
    }

    /**
     * メールアドレスが長すぎる場合のエラー
     *
     * @param  int  $maxLength  最大文字数
     */
    public static function tooLong(int $maxLength): self
    {
        return new self(
            sprintf('メールアドレスは%d文字以下で入力してください', $maxLength)
        );
    }

    /**
     * メールアドレスが空の場合のエラー
     */
    public static function empty(): self
    {
        return new self('メールアドレスを入力してください');
    }
}
