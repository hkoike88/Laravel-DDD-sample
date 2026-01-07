<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * パスワードポリシーバリデーションルール
 *
 * セキュリティ標準（01_PasswordPolicy.md）に基づくパスワードポリシーを検証する。
 * - 12文字以上
 * - 英大文字を含む
 * - 英小文字を含む
 * - 数字を含む
 * - 記号を含む
 *
 * @feature 001-security-preparation
 */
final class PasswordPolicyRule implements ValidationRule
{
    /**
     * 最小文字数
     */
    private const MIN_LENGTH = 12;

    /**
     * バリデーションを実行
     *
     * @param  string  $attribute  属性名
     * @param  mixed  $value  値
     * @param  Closure  $fail  失敗コールバック
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        $errors = [];

        // 最小文字数チェック
        if (mb_strlen($value) < self::MIN_LENGTH) {
            $errors[] = __('validation.password.min_length', ['min' => self::MIN_LENGTH]);
        }

        // 英大文字チェック
        if (! preg_match('/[A-Z]/', $value)) {
            $errors[] = __('validation.password.uppercase');
        }

        // 英小文字チェック
        if (! preg_match('/[a-z]/', $value)) {
            $errors[] = __('validation.password.lowercase');
        }

        // 数字チェック
        if (! preg_match('/[0-9]/', $value)) {
            $errors[] = __('validation.password.number');
        }

        // 記号チェック
        if (! preg_match('/[!@#$%^&*()_+\-=\[\]{};\':\"\\\\|,.<>\/?`~]/', $value)) {
            $errors[] = __('validation.password.symbol');
        }

        // エラーがあれば報告
        foreach ($errors as $error) {
            $fail($error);
        }
    }
}
