<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * パスワード漏洩チェックバリデーションルール
 *
 * Have I Been Pwned API を使用して、パスワードが過去に漏洩したものでないことを検証する。
 * API 障害時はスキップして警告ログを記録する（タイムアウト: 5秒）。
 *
 * @feature 001-security-preparation
 */
final class PasswordNotCompromisedRule implements ValidationRule
{
    /**
     * HIBP API のベースURL
     */
    private const HIBP_API_URL = 'https://api.pwnedpasswords.com/range/';

    /**
     * API タイムアウト（秒）
     */
    private const API_TIMEOUT = 5;

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
            return;
        }

        try {
            if ($this->isPasswordCompromised($value)) {
                $fail(__('validation.password.compromised'));
            }
        } catch (\Exception $e) {
            // API 障害時はスキップして警告ログを記録
            Log::channel('security')->warning('Have I Been Pwned API check skipped', [
                'reason' => $e->getMessage(),
                'attribute' => $attribute,
            ]);
        }
    }

    /**
     * パスワードが漏洩しているかチェック
     *
     * k-Anonymity モデルを使用して、パスワード全体をAPIに送信せずにチェックする。
     *
     * @param  string  $password  パスワード
     * @return bool 漏洩している場合 true
     *
     * @throws \Exception API エラー時
     */
    private function isPasswordCompromised(string $password): bool
    {
        // SHA-1 ハッシュを生成
        $sha1 = strtoupper(sha1($password));
        $prefix = substr($sha1, 0, 5);
        $suffix = substr($sha1, 5);

        // HIBP API にリクエスト
        $response = Http::timeout(self::API_TIMEOUT)
            ->get(self::HIBP_API_URL.$prefix);

        if ($response->failed()) {
            throw new \Exception('HIBP API request failed: '.$response->status());
        }

        // レスポンスからサフィックスを検索
        $hashes = explode("\n", $response->body());
        foreach ($hashes as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // 形式: SUFFIX:COUNT
            [$hashSuffix, $count] = explode(':', $line);
            if (strtoupper($hashSuffix) === $suffix) {
                return true; // 漏洩パスワードが見つかった
            }
        }

        return false;
    }
}
