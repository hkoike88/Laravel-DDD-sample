<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 無効な職員名例外
 *
 * 職員名が要件を満たさない場合にスローされる例外。
 */
class InvalidStaffNameException extends StaffDomainException
{
    /**
     * 職員名が空の場合のエラー
     */
    public static function empty(): self
    {
        return new self('職員名を入力してください');
    }

    /**
     * 職員名が長すぎる場合のエラー
     *
     * @param  int  $maxLength  最大文字数
     */
    public static function tooLong(int $maxLength): self
    {
        return new self(
            sprintf('職員名は%d文字以下で入力してください', $maxLength)
        );
    }
}
