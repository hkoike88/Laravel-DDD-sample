<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

/**
 * 認証失敗例外
 *
 * 認証情報が不正な場合にスローされる例外。
 * セキュリティのため、具体的な失敗理由は開示しない。
 */
class AuthenticationException extends StaffDomainException
{
    /**
     * 認証失敗エラーを生成
     */
    public static function invalidCredentials(): self
    {
        return new self('認証情報が正しくありません');
    }
}
