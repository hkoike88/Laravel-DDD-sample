<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\ResetPassword;

use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Packages\Domain\Staff\Application\DTO\StaffAccount\ResetPasswordOutput;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\Services\PasswordGenerator;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Infrastructure\AuditLog\StaffAuditLogger;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;

/**
 * パスワードリセットハンドラー
 *
 * 職員のパスワードをリセットし、新しい一時パスワードを生成する。
 *
 * @feature EPIC-004-staff-account-edit
 */
class ResetPasswordHandler
{
    /**
     * コンストラクタ
     *
     * @param  StaffRepositoryInterface  $staffRepository  職員リポジトリ
     * @param  PasswordGenerator  $passwordGenerator  パスワード生成サービス
     * @param  StaffAuditLogger  $auditLogger  監査ログサービス
     */
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
        private readonly PasswordGenerator $passwordGenerator,
        private readonly StaffAuditLogger $auditLogger,
    ) {}

    /**
     * パスワードリセットコマンドを実行
     *
     * @param  ResetPasswordCommand  $command  パスワードリセットコマンド
     * @return ResetPasswordOutput パスワードリセット結果
     *
     * @throws \Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException 職員が存在しない場合
     */
    public function handle(ResetPasswordCommand $command): ResetPasswordOutput
    {
        $staffId = StaffId::fromString($command->staffId);

        // 職員の存在確認（存在しない場合はStaffNotFoundExceptionがthrowされる）
        $this->staffRepository->find($staffId);

        // 新しい一時パスワードを生成
        $temporaryPassword = $this->passwordGenerator->generate();

        // Eloquentモデルでパスワードを更新
        $record = StaffRecord::find($command->staffId);
        assert($record instanceof StaffRecord);

        $record->password = Hash::make($temporaryPassword);
        $record->save();

        $now = new DateTimeImmutable;

        // 監査ログ記録
        $this->auditLogger->logPasswordReset(
            operatorId: $command->operatorId,
            targetStaffId: $command->staffId,
            timestamp: $now->format(DateTimeImmutable::ATOM),
        );

        return new ResetPasswordOutput(
            temporaryPassword: $temporaryPassword,
        );
    }
}
