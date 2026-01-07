<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Services;

/**
 * パスワード生成サービス
 *
 * 暗号学的に安全なランダムパスワードを生成する。
 * 生成されるパスワードは英大文字・英小文字・数字・記号を含む。
 *
 * @feature EPIC-003-staff-account-create
 */
class PasswordGenerator
{
    /** @var string 英小文字 */
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';

    /** @var string 英大文字 */
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** @var string 数字 */
    private const DIGITS = '0123456789';

    /** @var string 記号 */
    private const SYMBOLS = '!@#$%^&*';

    /** @var int デフォルトのパスワード長 */
    private const DEFAULT_LENGTH = 16;

    /**
     * ランダムパスワードを生成
     *
     * @param  int  $length  パスワードの長さ（デフォルト: 16）
     * @return string 生成されたパスワード
     */
    public function generate(int $length = self::DEFAULT_LENGTH): string
    {
        $chars = self::LOWERCASE.self::UPPERCASE.self::DIGITS.self::SYMBOLS;
        $charsLength = strlen($chars);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charsLength - 1)];
        }

        return $password;
    }
}
