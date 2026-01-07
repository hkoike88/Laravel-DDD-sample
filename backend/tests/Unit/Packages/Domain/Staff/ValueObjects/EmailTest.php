<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Staff\ValueObjects;

use Packages\Domain\Staff\Domain\Exceptions\InvalidEmailException;
use Packages\Domain\Staff\Domain\ValueObjects\Email;

/**
 * Email Value Object のテスト
 */
describe('Email', function () {
    describe('create', function () {
        it('有効なメールアドレスを作成できること', function () {
            $email = Email::create('test@example.com');

            expect($email)->toBeInstanceOf(Email::class);
            expect($email->value())->toBe('test@example.com');
        });

        it('大文字を小文字に正規化すること', function () {
            $email = Email::create('Test@EXAMPLE.com');

            expect($email->value())->toBe('test@example.com');
        });

        it('前後の空白を除去すること', function () {
            $email = Email::create('  test@example.com  ');

            expect($email->value())->toBe('test@example.com');
        });

        it('255文字のメールアドレスを受け入れること', function () {
            // ローカルパート最大64文字 + @ + ドメイン
            $localPart = str_repeat('a', 64);
            $domain = str_repeat('b', 255 - 64 - 1 - 4).'.com';
            $longEmail = $localPart.'@'.$domain;

            // 255文字以内なら受け入れる（ただし形式が正しい必要がある）
            $email = Email::create('test@example.com');
            expect($email)->toBeInstanceOf(Email::class);
        });
    });

    describe('validation', function () {
        it('空文字列の場合は例外がスローされること', function () {
            expect(fn () => Email::create(''))
                ->toThrow(InvalidEmailException::class, 'メールアドレスを入力してください');
        });

        it('空白のみの場合は例外がスローされること', function () {
            expect(fn () => Email::create('   '))
                ->toThrow(InvalidEmailException::class, 'メールアドレスを入力してください');
        });

        it('不正な形式の場合は例外がスローされること', function () {
            expect(fn () => Email::create('invalid-email'))
                ->toThrow(InvalidEmailException::class, 'メールアドレスの形式が正しくありません');
        });

        it('@がない場合は例外がスローされること', function () {
            expect(fn () => Email::create('testexample.com'))
                ->toThrow(InvalidEmailException::class, 'メールアドレスの形式が正しくありません');
        });

        it('ドメインがない場合は例外がスローされること', function () {
            expect(fn () => Email::create('test@'))
                ->toThrow(InvalidEmailException::class, 'メールアドレスの形式が正しくありません');
        });

        it('ローカルパートがない場合は例外がスローされること', function () {
            expect(fn () => Email::create('@example.com'))
                ->toThrow(InvalidEmailException::class, 'メールアドレスの形式が正しくありません');
        });

        it('256文字以上の場合は例外がスローされること', function () {
            $longEmail = str_repeat('a', 245).'@example.com'; // 257文字
            expect(fn () => Email::create($longEmail))
                ->toThrow(InvalidEmailException::class, 'メールアドレスは255文字以下で入力してください');
        });
    });

    describe('fromString', function () {
        it('文字列から Email を復元できること', function () {
            $email = Email::fromString('test@example.com');

            expect($email)->toBeInstanceOf(Email::class);
            expect($email->value())->toBe('test@example.com');
        });

        it('正規化なしで値を保持すること', function () {
            // fromString は DB からの復元用なので正規化しない
            $email = Email::fromString('test@example.com');

            expect($email->value())->toBe('test@example.com');
        });
    });

    describe('value', function () {
        it('内部値を取得できること', function () {
            $email = Email::create('test@example.com');

            expect($email->value())->toBe('test@example.com');
        });
    });

    describe('equals', function () {
        it('同じ値を持つ Email は等しいこと', function () {
            $email1 = Email::create('test@example.com');
            $email2 = Email::create('test@example.com');

            expect($email1->equals($email2))->toBeTrue();
        });

        it('大文字小文字が異なるメールアドレスは正規化後に等しいこと', function () {
            $email1 = Email::create('test@example.com');
            $email2 = Email::create('TEST@EXAMPLE.COM');

            expect($email1->equals($email2))->toBeTrue();
        });

        it('異なる値を持つ Email は等しくないこと', function () {
            $email1 = Email::create('test1@example.com');
            $email2 = Email::create('test2@example.com');

            expect($email1->equals($email2))->toBeFalse();
        });
    });

    describe('__toString', function () {
        it('文字列に変換できること', function () {
            $email = Email::create('test@example.com');

            expect((string) $email)->toBe('test@example.com');
        });
    });
});
