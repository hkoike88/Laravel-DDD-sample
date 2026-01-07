<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\Model;

use DateTimeImmutable;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Staff エンティティのテスト
 */
describe('Staff', function () {
    describe('create', function () {
        it('新規職員を作成できること', function () {
            $id = StaffId::generate();
            $email = Email::create('test@example.com');
            $password = Password::fromPlainText('password123');
            $name = StaffName::create('山田太郎');

            $staff = Staff::create(
                id: $id,
                email: $email,
                password: $password,
                name: $name,
            );

            expect($staff)->toBeInstanceOf(Staff::class);
            expect($staff->id()->equals($id))->toBeTrue();
            expect($staff->email()->equals($email))->toBeTrue();
            expect($staff->password()->hashedValue())->toBe($password->hashedValue());
            expect($staff->name()->equals($name))->toBeTrue();
        });

        it('初期状態はアンロック状態であること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->isLocked())->toBeFalse();
        });

        it('初期状態の失敗回数は 0 であること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->failedLoginAttempts())->toBe(0);
        });

        it('初期状態のロック日時は null であること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->lockedAt())->toBeNull();
        });

        it('初期状態の管理者フラグは false であること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->isAdmin())->toBeFalse();
        });

        it('管理者フラグを true に設定して作成できること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: true,
            );

            expect($staff->isAdmin())->toBeTrue();
        });
    });

    describe('reconstruct', function () {
        it('永続化データから職員を復元できること', function () {
            $id = StaffId::generate();
            $email = Email::create('test@example.com');
            $password = Password::fromPlainText('password123');
            $name = StaffName::create('山田太郎');
            $isAdmin = true;
            $isLocked = true;
            $failedLoginAttempts = 3;
            $lockedAt = new DateTimeImmutable('2024-01-01 12:00:00');

            $staff = Staff::reconstruct(
                id: $id,
                email: $email,
                password: $password,
                name: $name,
                isAdmin: $isAdmin,
                isLocked: $isLocked,
                failedLoginAttempts: $failedLoginAttempts,
                lockedAt: $lockedAt,
            );

            expect($staff->id()->equals($id))->toBeTrue();
            expect($staff->email()->equals($email))->toBeTrue();
            expect($staff->password()->hashedValue())->toBe($password->hashedValue());
            expect($staff->name()->equals($name))->toBeTrue();
            expect($staff->isAdmin())->toBe($isAdmin);
            expect($staff->isLocked())->toBe($isLocked);
            expect($staff->failedLoginAttempts())->toBe($failedLoginAttempts);
            expect($staff->lockedAt())->toBe($lockedAt);
        });

        it('アンロック状態の職員を復元できること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: false,
                failedLoginAttempts: 0,
                lockedAt: null,
            );

            expect($staff->isLocked())->toBeFalse();
            expect($staff->failedLoginAttempts())->toBe(0);
            expect($staff->lockedAt())->toBeNull();
        });

        it('管理者として職員を復元できること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('admin@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('管理者'),
                isAdmin: true,
                isLocked: false,
                failedLoginAttempts: 0,
                lockedAt: null,
            );

            expect($staff->isAdmin())->toBeTrue();
        });
    });

    describe('getters', function () {
        it('id() で職員IDを取得できること', function () {
            $id = StaffId::generate();
            $staff = createTestStaff($id);

            expect($staff->id()->equals($id))->toBeTrue();
        });

        it('email() でメールアドレスを取得できること', function () {
            $email = Email::create('test@example.com');
            $staff = createTestStaff(email: $email);

            expect($staff->email()->equals($email))->toBeTrue();
        });

        it('password() でパスワードを取得できること', function () {
            $password = Password::fromPlainText('password123');
            $staff = createTestStaff(password: $password);

            expect($staff->password()->hashedValue())->toBe($password->hashedValue());
        });

        it('name() で職員名を取得できること', function () {
            $name = StaffName::create('山田太郎');
            $staff = createTestStaff(name: $name);

            expect($staff->name()->equals($name))->toBeTrue();
        });

        it('isAdmin() で管理者フラグを取得できること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: true,
                isLocked: false,
                failedLoginAttempts: 0,
                lockedAt: null,
            );

            expect($staff->isAdmin())->toBeTrue();
        });

        it('isLocked() でロック状態を取得できること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: true,
                failedLoginAttempts: 5,
                lockedAt: new DateTimeImmutable,
            );

            expect($staff->isLocked())->toBeTrue();
        });

        it('failedLoginAttempts() で失敗回数を取得できること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: false,
                failedLoginAttempts: 3,
                lockedAt: null,
            );

            expect($staff->failedLoginAttempts())->toBe(3);
        });

        it('lockedAt() でロック日時を取得できること', function () {
            $lockedAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: true,
                failedLoginAttempts: 5,
                lockedAt: $lockedAt,
            );

            expect($staff->lockedAt())->toBe($lockedAt);
        });
    });

    describe('verifyPassword', function () {
        it('正しいパスワードを検証できること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->verifyPassword('password123'))->toBeTrue();
        });

        it('間違ったパスワードを拒否すること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->verifyPassword('wrongpassword'))->toBeFalse();
        });

        it('空文字列を拒否すること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            expect($staff->verifyPassword(''))->toBeFalse();
        });

        it('復元した職員のパスワードを検証できること', function () {
            $originalPassword = Password::fromPlainText('password123');
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromHash($originalPassword->hashedValue()),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: false,
                failedLoginAttempts: 0,
                lockedAt: null,
            );

            expect($staff->verifyPassword('password123'))->toBeTrue();
            expect($staff->verifyPassword('wrongpassword'))->toBeFalse();
        });
    });

    describe('lock', function () {
        it('アカウントをロックできること', function () {
            $staff = createTestStaff();

            expect($staff->isLocked())->toBeFalse();

            $staff->lock();

            expect($staff->isLocked())->toBeTrue();
            expect($staff->lockedAt())->toBeInstanceOf(DateTimeImmutable::class);
        });

        it('ロック日時が現在時刻付近であること', function () {
            $staff = createTestStaff();
            $before = new DateTimeImmutable;

            $staff->lock();

            $after = new DateTimeImmutable;

            expect($staff->lockedAt()->getTimestamp())
                ->toBeGreaterThanOrEqual($before->getTimestamp());
            expect($staff->lockedAt()->getTimestamp())
                ->toBeLessThanOrEqual($after->getTimestamp());
        });
    });

    describe('unlock', function () {
        it('アカウントをアンロックできること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: true,
                failedLoginAttempts: 5,
                lockedAt: new DateTimeImmutable,
            );

            expect($staff->isLocked())->toBeTrue();

            $staff->unlock();

            expect($staff->isLocked())->toBeFalse();
            expect($staff->lockedAt())->toBeNull();
            expect($staff->failedLoginAttempts())->toBe(0);
        });
    });

    describe('incrementFailedLoginAttempts', function () {
        it('ログイン失敗回数をインクリメントできること', function () {
            $staff = createTestStaff();

            expect($staff->failedLoginAttempts())->toBe(0);

            $staff->incrementFailedLoginAttempts();
            expect($staff->failedLoginAttempts())->toBe(1);

            $staff->incrementFailedLoginAttempts();
            expect($staff->failedLoginAttempts())->toBe(2);

            $staff->incrementFailedLoginAttempts();
            expect($staff->failedLoginAttempts())->toBe(3);
        });
    });

    describe('resetFailedLoginAttempts', function () {
        it('ログイン失敗回数をリセットできること', function () {
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: false,
                failedLoginAttempts: 5,
                lockedAt: null,
            );

            expect($staff->failedLoginAttempts())->toBe(5);

            $staff->resetFailedLoginAttempts();

            expect($staff->failedLoginAttempts())->toBe(0);
        });
    });

    describe('account lock workflow', function () {
        it('ログイン失敗→ロック→アンロックのワークフローが動作すること', function () {
            $staff = createTestStaff();

            // 初期状態
            expect($staff->isLocked())->toBeFalse();
            expect($staff->failedLoginAttempts())->toBe(0);

            // ログイン失敗を3回記録
            $staff->incrementFailedLoginAttempts();
            $staff->incrementFailedLoginAttempts();
            $staff->incrementFailedLoginAttempts();
            expect($staff->failedLoginAttempts())->toBe(3);

            // アカウントをロック
            $staff->lock();
            expect($staff->isLocked())->toBeTrue();
            expect($staff->lockedAt())->not->toBeNull();

            // アカウントをアンロック
            $staff->unlock();
            expect($staff->isLocked())->toBeFalse();
            expect($staff->lockedAt())->toBeNull();
            expect($staff->failedLoginAttempts())->toBe(0);
        });

        it('ログイン成功後に失敗回数がリセットされること', function () {
            $staff = createTestStaff();

            // ログイン失敗を2回記録
            $staff->incrementFailedLoginAttempts();
            $staff->incrementFailedLoginAttempts();
            expect($staff->failedLoginAttempts())->toBe(2);

            // ログイン成功時にリセット
            $staff->resetFailedLoginAttempts();
            expect($staff->failedLoginAttempts())->toBe(0);
        });
    });
});

/**
 * テスト用の Staff インスタンスを作成
 */
function createTestStaff(
    ?StaffId $id = null,
    ?Email $email = null,
    ?Password $password = null,
    ?StaffName $name = null,
): Staff {
    return Staff::create(
        id: $id ?? StaffId::generate(),
        email: $email ?? Email::create('test@example.com'),
        password: $password ?? Password::fromPlainText('password123'),
        name: $name ?? StaffName::create('山田太郎'),
    );
}
