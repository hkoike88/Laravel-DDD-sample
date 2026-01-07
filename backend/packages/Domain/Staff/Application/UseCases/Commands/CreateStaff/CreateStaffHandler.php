<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\CreateStaff;

use DateTimeImmutable;
use Packages\Domain\Staff\Application\DTO\StaffAccount\CreateStaffOutput;
use Packages\Domain\Staff\Domain\Exceptions\DuplicateEmailException;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\Services\PasswordGenerator;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Packages\Domain\Staff\Infrastructure\AuditLog\StaffAuditLogger;

/**
 * 職員作成ハンドラー
 *
 * 新規職員アカウントを作成し、初期パスワードを生成する。
 *
 * @feature EPIC-003-staff-account-create
 */
class CreateStaffHandler
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
     * 職員作成コマンドを実行
     *
     * @param  CreateStaffCommand  $command  職員作成コマンド
     * @return CreateStaffOutput 職員作成結果（初期パスワードを含む）
     *
     * @throws DuplicateEmailException メールアドレスが既に登録されている場合
     */
    public function handle(CreateStaffCommand $command): CreateStaffOutput
    {
        $input = $command->input;

        // メールアドレスの一意性チェック
        $email = Email::create($input->email);
        if ($this->staffRepository->existsByEmail($email)) {
            throw new DuplicateEmailException($email);
        }

        // 初期パスワード生成
        $temporaryPassword = $this->passwordGenerator->generate();

        // 職員エンティティ作成
        $staffId = StaffId::generate();
        $staff = Staff::create(
            id: $staffId,
            email: $email,
            password: Password::fromPlainText($temporaryPassword),
            name: StaffName::create($input->name),
            isAdmin: $input->role === 'admin',
        );

        // 永続化
        $this->staffRepository->save($staff);

        // 監査ログ記録
        $now = new DateTimeImmutable;
        $this->auditLogger->logStaffCreated(
            operatorId: $command->operatorId,
            targetStaffId: $staffId->value(),
            timestamp: $now->format(DateTimeImmutable::ATOM),
        );

        // 出力DTO作成
        return new CreateStaffOutput(
            id: $staffId->value(),
            name: $staff->name()->value(),
            email: $staff->email()->value(),
            role: $input->role,
            temporaryPassword: $temporaryPassword,
            createdAt: $now->format(DateTimeImmutable::ATOM),
        );
    }
}
