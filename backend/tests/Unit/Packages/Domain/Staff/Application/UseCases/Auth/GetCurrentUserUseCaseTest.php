<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\Application\UseCases\Auth;

use Mockery;
use Packages\Domain\Staff\Application\DTO\Auth\StaffResponse;
use Packages\Domain\Staff\Application\UseCases\Auth\GetCurrentUserUseCase;
use Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Tests\TestCase;

uses(TestCase::class);

/**
 * GetCurrentUserUseCase ユニットテスト
 */
describe('GetCurrentUserUseCase', function () {
    beforeEach(function () {
        $this->staffRepository = Mockery::mock(StaffRepositoryInterface::class);
        $this->useCase = new GetCurrentUserUseCase($this->staffRepository);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('正常系', function () {
        it('職員ID から職員情報を取得できること', function () {
            $staffId = StaffId::generate();
            $staff = Staff::create(
                id: $staffId,
                email: Email::create('test@example.com'),
                password: Password::fromPlainText('password123'),
                name: StaffName::create('山田太郎'),
            );

            $this->staffRepository
                ->shouldReceive('find')
                ->once()
                ->with(Mockery::type(StaffId::class))
                ->andReturn($staff);

            $response = $this->useCase->execute($staffId->value());

            expect($response)->toBeInstanceOf(StaffResponse::class);
            expect($response->id)->toBe($staffId->value());
            expect($response->email)->toBe('test@example.com');
            expect($response->name)->toBe('山田太郎');
        });
    });

    describe('異常系', function () {
        it('存在しない職員ID で StaffNotFoundException がスローされること', function () {
            $staffId = StaffId::generate();

            $this->staffRepository
                ->shouldReceive('find')
                ->once()
                ->andThrow(new StaffNotFoundException($staffId));

            expect(fn () => $this->useCase->execute($staffId->value()))
                ->toThrow(StaffNotFoundException::class);
        });
    });
});
