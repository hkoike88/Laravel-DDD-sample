<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\ChangePassword;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException;
use Packages\Domain\Staff\Domain\Services\PasswordHistoryService;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;

/**
 * パスワード変更アクション
 *
 * 職員のパスワードを変更し、履歴を記録する。
 *
 * @feature 001-security-preparation
 */
final class ChangePasswordAction
{
    /**
     * コンストラクタ
     *
     * @param  PasswordHistoryService  $passwordHistoryService  パスワード履歴サービス
     */
    public function __construct(
        private readonly PasswordHistoryService $passwordHistoryService,
    ) {}

    /**
     * パスワード変更を実行
     *
     * @param  StaffId  $staffId  職員ID
     * @param  string  $newPassword  新しいパスワード（平文）
     *
     * @throws StaffNotFoundException 職員が存在しない場合
     * @throws \Exception トランザクション失敗時
     */
    public function execute(StaffId $staffId, string $newPassword): void
    {
        DB::transaction(function () use ($staffId, $newPassword) {
            // 職員を取得
            $staffRecord = StaffRecord::find($staffId->value());
            if ($staffRecord === null) {
                throw new StaffNotFoundException($staffId);
            }

            // 新しいパスワードをハッシュ化
            $hashedPassword = Hash::make($newPassword);

            // パスワード履歴に追加
            $this->passwordHistoryService->addToHistory($staffId, $hashedPassword);

            // 職員のパスワードを更新
            $staffRecord->password = $hashedPassword;
            $staffRecord->save();
        });
    }
}
