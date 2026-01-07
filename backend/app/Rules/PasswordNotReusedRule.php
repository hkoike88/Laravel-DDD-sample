<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Packages\Domain\Staff\Domain\Repositories\PasswordHistoryRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * パスワード再利用禁止バリデーションルール
 *
 * 過去5世代のパスワードと同一でないことを検証する。
 *
 * @feature 001-security-preparation
 */
final class PasswordNotReusedRule implements ValidationRule
{
    /**
     * 保持する履歴世代数
     */
    private const HISTORY_COUNT = 5;

    /**
     * コンストラクタ
     *
     * @param  StaffId  $staffId  検証対象の職員ID
     * @param  PasswordHistoryRepositoryInterface  $repository  パスワード履歴リポジトリ
     */
    public function __construct(
        private readonly StaffId $staffId,
        private readonly PasswordHistoryRepositoryInterface $repository,
    ) {}

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

        // 過去5世代のパスワード履歴を取得
        $histories = $this->repository->findRecentByStaffId($this->staffId, self::HISTORY_COUNT);

        // 各履歴と照合
        foreach ($histories as $history) {
            if ($history->matches($value)) {
                $fail(__('validation.password.reused'));

                return;
            }
        }
    }
}
