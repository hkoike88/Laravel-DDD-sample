<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\Auth;

/**
 * ログインリクエスト DTO
 *
 * ログイン処理に必要な認証情報を保持する。
 */
final readonly class LoginRequest
{
    /**
     * コンストラクタ
     *
     * @param  string  $email  メールアドレス
     * @param  string  $password  パスワード（平文）
     */
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
