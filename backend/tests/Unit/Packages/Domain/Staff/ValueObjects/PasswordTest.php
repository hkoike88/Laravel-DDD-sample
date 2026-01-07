<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\ValueObjects;

use Packages\Domain\Staff\Domain\Exceptions\InvalidPasswordException;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Password Value Object のテスト
 */
describe('Password', function () {
    describe('fromPlainText', function () {
        it('平文パスワードからハッシュ化して生成できること', function () {
            $password = Password::fromPlainText('password123');

            expect($password)->toBeInstanceOf(Password::class);
            expect($password->hashedValue())->not->toBe('password123');
            // bcrypt hash は $2y$ で始まる
            expect($password->hashedValue())->toStartWith('$2y$');
        });

        it('同じパスワードでも異なるハッシュが生成されること', function () {
            $password1 = Password::fromPlainText('password123');
            $password2 = Password::fromPlainText('password123');

            // bcrypt は毎回異なるソルトを使用するため、ハッシュは異なる
            expect($password1->hashedValue())->not->toBe($password2->hashedValue());
        });

        it('8文字のパスワードを受け入れること', function () {
            $password = Password::fromPlainText('12345678');

            expect($password)->toBeInstanceOf(Password::class);
        });

        it('72文字のパスワードを受け入れること', function () {
            $password = Password::fromPlainText(str_repeat('a', 72));

            expect($password)->toBeInstanceOf(Password::class);
        });
    });

    describe('validation', function () {
        it('空文字列の場合は例外がスローされること', function () {
            expect(fn () => Password::fromPlainText(''))
                ->toThrow(InvalidPasswordException::class, 'パスワードを入力してください');
        });

        it('7文字以下の場合は例外がスローされること', function () {
            expect(fn () => Password::fromPlainText('1234567'))
                ->toThrow(InvalidPasswordException::class, 'パスワードは8文字以上で入力してください');
        });

        it('73文字以上の場合は例外がスローされること', function () {
            expect(fn () => Password::fromPlainText(str_repeat('a', 73)))
                ->toThrow(InvalidPasswordException::class, 'パスワードは72文字以下で入力してください');
        });
    });

    describe('fromHash', function () {
        it('ハッシュ値から復元できること', function () {
            $originalPassword = Password::fromPlainText('password123');
            $restoredPassword = Password::fromHash($originalPassword->hashedValue());

            expect($restoredPassword)->toBeInstanceOf(Password::class);
            expect($restoredPassword->hashedValue())->toBe($originalPassword->hashedValue());
        });
    });

    describe('verify', function () {
        it('正しいパスワードを検証できること', function () {
            $password = Password::fromPlainText('password123');

            expect($password->verify('password123'))->toBeTrue();
        });

        it('間違ったパスワードを拒否すること', function () {
            $password = Password::fromPlainText('password123');

            expect($password->verify('wrongpassword'))->toBeFalse();
        });

        it('空文字列を拒否すること', function () {
            $password = Password::fromPlainText('password123');

            expect($password->verify(''))->toBeFalse();
        });

        it('fromHash で復元したパスワードを検証できること', function () {
            $originalPassword = Password::fromPlainText('password123');
            $restoredPassword = Password::fromHash($originalPassword->hashedValue());

            expect($restoredPassword->verify('password123'))->toBeTrue();
            expect($restoredPassword->verify('wrongpassword'))->toBeFalse();
        });
    });

    describe('hashedValue', function () {
        it('ハッシュ値を取得できること', function () {
            $password = Password::fromPlainText('password123');

            expect($password->hashedValue())->toBeString();
            // bcrypt hash は 60 文字
            expect(strlen($password->hashedValue()))->toBe(60);
        });
    });
});
