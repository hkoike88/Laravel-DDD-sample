<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\Application\UseCases\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Mockery;
use Packages\Domain\Staff\Application\UseCases\Auth\LogoutUseCase;
use Tests\TestCase;

uses(TestCase::class);

/**
 * LogoutUseCase ユニットテスト
 */
describe('LogoutUseCase', function () {
    afterEach(function () {
        Mockery::close();
    });

    describe('正常系', function () {
        it('ログアウト処理が実行されること', function () {
            $guard = Mockery::mock(StatefulGuard::class);

            $guard->shouldReceive('logout')
                ->once();

            $useCase = new LogoutUseCase($guard);
            $useCase->execute();

            // 例外がスローされなければ成功
            expect(true)->toBeTrue();
        });
    });
});
