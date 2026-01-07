<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\DTO\StaffAccount;

/**
 * パスワードリセットレスポンスの出力データ
 *
 * @feature EPIC-004-staff-account-edit
 */
final readonly class ResetPasswordOutput
{
    /**
     * コンストラクタ
     *
     * @param  string  $temporaryPassword  一時パスワード（平文、この場面でのみ使用）
     */
    public function __construct(
        public string $temporaryPassword,
    ) {}

    /**
     * 配列に変換（JSON レスポンス用）
     *
     * @return array{
     *   message: string,
     *   temporaryPassword: string
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => 'パスワードをリセットしました',
            'temporaryPassword' => $this->temporaryPassword,
        ];
    }
}
