<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\Application\UseCases\Auth;

use DateTimeImmutable;
use Mockery;
use Packages\Domain\Staff\Application\DTO\Auth\LoginRequest;
use Packages\Domain\Staff\Application\DTO\Auth\StaffResponse;
use Packages\Domain\Staff\Application\UseCases\Auth\LoginUseCase;
use Packages\Domain\Staff\Domain\Exceptions\AccountLockedException;
use Packages\Domain\Staff\Domain\Exceptions\AuthenticationException;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Tests\TestCase;

uses(TestCase::class);

/**
 * LoginUseCase ユニットテスト
 */
describe('LoginUseCase', function () {
    beforeEach(function () {
        $this->staffRepository = Mockery::mock(StaffRepositoryInterface::class);
        $this->useCase = new LoginUseCase($this->staffRepository);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('正常系', function () {
        it('正しい認証情報でログインできること', function () {
            $email = 'test@example.com';
            $password = 'password123';

            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create($email),
                password: Password::fromPlainText($password),
                name: StaffName::create('山田太郎'),
            );

            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->with(Mockery::type(Email::class))
                ->andReturn($staff);

            $this->staffRepository
                ->shouldReceive('save')
                ->once()
                ->with(Mockery::type(Staff::class));

            $request = new LoginRequest(
                email: $email,
                password: $password,
            );

            $response = $this->useCase->execute($request);

            expect($response)->toBeInstanceOf(StaffResponse::class);
            expect($response->email)->toBe($email);
            expect($response->name)->toBe('山田太郎');
        });

        it('ログイン成功時に失敗カウントがリセットされること', function () {
            $email = 'test@example.com';
            $password = 'password123';

            // 失敗カウントが2の状態でスタート
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create($email),
                password: Password::fromPlainText($password),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: false,
                failedLoginAttempts: 2,
                lockedAt: null,
            );

            $savedStaff = null;
            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->andReturn($staff);

            $this->staffRepository
                ->shouldReceive('save')
                ->once()
                ->with(Mockery::on(function ($s) use (&$savedStaff) {
                    $savedStaff = $s;

                    return true;
                }));

            $request = new LoginRequest(
                email: $email,
                password: $password,
            );

            $this->useCase->execute($request);

            expect($savedStaff->failedLoginAttempts())->toBe(0);
        });
    });

    describe('異常系', function () {
        it('存在しないメールアドレスで AuthenticationException がスローされること', function () {
            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->andReturn(null);

            $request = new LoginRequest(
                email: 'nonexistent@example.com',
                password: 'password123',
            );

            expect(fn () => $this->useCase->execute($request))
                ->toThrow(AuthenticationException::class);
        });

        it('間違ったパスワードで AuthenticationException がスローされること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('correct_password'),
                name: StaffName::create('山田太郎'),
            );

            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->andReturn($staff);

            $this->staffRepository
                ->shouldReceive('save')
                ->once();

            $request = new LoginRequest(
                email: 'test@example.com',
                password: 'wrong_password',
            );

            expect(fn () => $this->useCase->execute($request))
                ->toThrow(AuthenticationException::class);
        });

        it('パスワード間違いで失敗カウントがインクリメントされること', function () {
            $staff = Staff::create(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('correct_password'),
                name: StaffName::create('山田太郎'),
            );

            $savedStaff = null;
            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->andReturn($staff);

            $this->staffRepository
                ->shouldReceive('save')
                ->once()
                ->with(Mockery::on(function ($s) use (&$savedStaff) {
                    $savedStaff = $s;

                    return true;
                }));

            $request = new LoginRequest(
                email: 'test@example.com',
                password: 'wrong_password',
            );

            try {
                $this->useCase->execute($request);
            } catch (AuthenticationException) {
                // 期待通り例外がスロー
            }

            expect($savedStaff->failedLoginAttempts())->toBe(1);
        });

        it('ロックされたアカウントで AccountLockedException がスローされること', function () {
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

            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->andReturn($staff);

            $request = new LoginRequest(
                email: 'test@example.com',
                password: 'password123',
            );

            expect(fn () => $this->useCase->execute($request))
                ->toThrow(AccountLockedException::class);
        });

        it('5回失敗でアカウントがロックされること', function () {
            // 4回失敗済みの状態でスタート
            $staff = Staff::reconstruct(
                id: StaffId::generate(),
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('correct_password'),
                name: StaffName::create('山田太郎'),
                isAdmin: false,
                isLocked: false,
                failedLoginAttempts: 4,
                lockedAt: null,
            );

            $savedStaff = null;
            $this->staffRepository
                ->shouldReceive('findByEmail')
                ->once()
                ->andReturn($staff);

            $this->staffRepository
                ->shouldReceive('save')
                ->once()
                ->with(Mockery::on(function ($s) use (&$savedStaff) {
                    $savedStaff = $s;

                    return true;
                }));

            $request = new LoginRequest(
                email: 'test@example.com',
                password: 'wrong_password',
            );

            try {
                $this->useCase->execute($request);
            } catch (AuthenticationException) {
                // 期待通り例外がスロー
            }

            expect($savedStaff->isLocked())->toBeTrue();
            expect($savedStaff->failedLoginAttempts())->toBe(5);
        });
    });
});
