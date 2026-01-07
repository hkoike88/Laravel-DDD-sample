<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\UpdateStaff;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Packages\Domain\Staff\Application\DTO\StaffAccount\UpdateStaffOutput;
use Packages\Domain\Staff\Domain\Exceptions\DuplicateEmailException;
use Packages\Domain\Staff\Domain\Exceptions\LastAdminProtectionException;
use Packages\Domain\Staff\Domain\Exceptions\OptimisticLockException;
use Packages\Domain\Staff\Domain\Exceptions\SelfRoleChangeException;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Infrastructure\AuditLog\StaffAuditLogger;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;

/**
 * 職員更新ハンドラー
 *
 * 既存の職員アカウント情報を更新する。
 * 楽観的ロック、自己権限変更防止、最後の管理者保護などのビジネスルールを適用。
 *
 * @feature EPIC-004-staff-account-edit
 */
class UpdateStaffHandler
{
    /**
     * コンストラクタ
     *
     * @param  StaffRepositoryInterface  $staffRepository  職員リポジトリ
     * @param  StaffAuditLogger  $auditLogger  監査ログサービス
     */
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
        private readonly StaffAuditLogger $auditLogger,
    ) {}

    /**
     * 職員更新コマンドを実行
     *
     * トランザクション内で処理を行い、競合状態を防止する。
     * 最後の管理者保護のチェック時は排他ロックを取得する。
     *
     * @param  UpdateStaffCommand  $command  職員更新コマンド
     * @return UpdateStaffOutput 職員更新結果
     *
     * @throws OptimisticLockException 楽観的ロック競合
     * @throws SelfRoleChangeException 自己権限変更
     * @throws LastAdminProtectionException 最後の管理者保護
     * @throws DuplicateEmailException メールアドレス重複
     * @throws \Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException 職員が存在しない場合
     */
    public function handle(UpdateStaffCommand $command): UpdateStaffOutput
    {
        return DB::transaction(function () use ($command) {
            $input = $command->input;
            $staffId = StaffId::fromString($command->staffId);

            // 現在の職員を取得（存在しない場合はStaffNotFoundExceptionがthrowされる）
            $currentStaff = $this->staffRepository->find($staffId);

            // EloquentレコードからupdatedAtを取得（上記でfindが成功しているので必ず存在する）
            $record = StaffRecord::find($command->staffId);
            assert($record instanceof StaffRecord);

            // 楽観的ロック検証
            $this->validateOptimisticLock($record, $input->updatedAt);

            // 権限変更の検証
            $isRoleChanging = $currentStaff->isAdmin() !== $input->isAdmin();
            if ($isRoleChanging) {
                // 自己権限変更の防止
                $this->validateSelfRoleChange($command->staffId, $command->operatorId);

                // 最後の管理者保護（排他ロック付き）
                $this->validateLastAdminProtection($currentStaff->isAdmin(), $input->isAdmin());
            }

            // メールアドレス一意性検証（自分以外）
            $newEmail = Email::create($input->email);
            if ($newEmail->value() !== $currentStaff->email()->value()) {
                if ($this->staffRepository->existsByEmailExcludingId($newEmail, $staffId)) {
                    throw new DuplicateEmailException($newEmail);
                }
            }

            // 変更内容を記録（監査ログ用）
            $changes = $this->buildChanges($currentStaff, $input);

            // Eloquentモデルを直接更新（不変プロパティを含むため）
            $record->name = $input->name;
            $record->email = $input->email;
            $record->is_admin = $input->isAdmin();
            $record->save();

            // 更新後のタイムスタンプを取得
            $record->refresh();
            $now = new DateTimeImmutable;

            // 監査ログ記録
            $this->auditLogger->logStaffUpdated(
                operatorId: $command->operatorId,
                targetStaffId: $command->staffId,
                changes: $changes,
                timestamp: $now->format(DateTimeImmutable::ATOM),
            );

            // 出力DTO作成
            return new UpdateStaffOutput(
                id: $command->staffId,
                name: $input->name,
                email: $input->email,
                role: $input->role,
                updatedAt: $record->updated_at->toIso8601String(),
            );
        });
    }

    /**
     * 楽観的ロック検証
     *
     * @param  StaffRecord  $record  現在のレコード
     * @param  string  $clientUpdatedAt  クライアントが送信した更新日時
     *
     * @throws OptimisticLockException 競合が検出された場合
     */
    private function validateOptimisticLock(StaffRecord $record, string $clientUpdatedAt): void
    {
        $currentUpdatedAt = $record->updated_at->toIso8601String();
        $clientDate = new DateTimeImmutable($clientUpdatedAt);

        // 秒単位で比較（マイクロ秒の差異を無視）
        if ($record->updated_at->getTimestamp() !== $clientDate->getTimestamp()) {
            throw new OptimisticLockException;
        }
    }

    /**
     * 自己権限変更の検証
     *
     * @param  string  $targetStaffId  対象職員ID
     * @param  string  $operatorId  操作者ID
     *
     * @throws SelfRoleChangeException 自己権限変更の場合
     */
    private function validateSelfRoleChange(string $targetStaffId, string $operatorId): void
    {
        if ($targetStaffId === $operatorId) {
            throw new SelfRoleChangeException;
        }
    }

    /**
     * 最後の管理者保護の検証
     *
     * 排他ロック付きで管理者数をカウントし、競合状態を防止する。
     * トランザクション内で呼び出すこと。
     *
     * @param  bool  $currentIsAdmin  現在の権限
     * @param  bool  $newIsAdmin  新しい権限
     *
     * @throws LastAdminProtectionException 最後の管理者を降格しようとした場合
     */
    private function validateLastAdminProtection(bool $currentIsAdmin, bool $newIsAdmin): void
    {
        // 管理者 → 一般職員 への変更時のみチェック
        if ($currentIsAdmin && ! $newIsAdmin) {
            // 排他ロック付きでカウント（競合状態を防止）
            $adminCount = $this->staffRepository->countAdminsForUpdate();
            if ($adminCount <= 1) {
                throw new LastAdminProtectionException;
            }
        }
    }

    /**
     * 変更内容を構築
     *
     * @param  \Packages\Domain\Staff\Domain\Model\Staff  $currentStaff  現在の職員
     * @param  \Packages\Domain\Staff\Application\DTO\StaffAccount\UpdateStaffInput  $input  入力データ
     * @return array<string, array{before: mixed, after: mixed}> 変更内容
     */
    private function buildChanges($currentStaff, $input): array
    {
        $changes = [];

        if ($currentStaff->name()->value() !== $input->name) {
            $changes['name'] = [
                'before' => $currentStaff->name()->value(),
                'after' => $input->name,
            ];
        }

        if ($currentStaff->email()->value() !== $input->email) {
            $changes['email'] = [
                'before' => $currentStaff->email()->value(),
                'after' => $input->email,
            ];
        }

        $currentRole = $currentStaff->isAdmin() ? 'admin' : 'staff';
        if ($currentRole !== $input->role) {
            $changes['role'] = [
                'before' => $currentRole,
                'after' => $input->role,
            ];
        }

        return $changes;
    }
}
