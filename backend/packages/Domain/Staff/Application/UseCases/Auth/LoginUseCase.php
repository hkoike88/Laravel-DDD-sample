<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Auth;

use Packages\Domain\Staff\Application\DTO\Auth\LoginRequest;
use Packages\Domain\Staff\Application\DTO\Auth\StaffResponse;
use Packages\Domain\Staff\Domain\Exceptions\AccountLockedException;
use Packages\Domain\Staff\Domain\Exceptions\AuthenticationException;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\ValueObjects\Email;

/**
 * ログインユースケース
 *
 * 職員のログイン認証を処理する。
 * - メールアドレスとパスワードの検証
 * - ログイン失敗時のカウント管理
 * - アカウントロック制御（5回失敗でロック）
 */
final readonly class LoginUseCase
{
    /**
     * アカウントロックまでの最大失敗回数
     */
    private const MAX_FAILED_ATTEMPTS = 5;

    /**
     * アカウントロック時間（秒）
     */
    private const LOCK_DURATION_SECONDS = 1800; // 30分

    /**
     * コンストラクタ
     *
     * @param  StaffRepositoryInterface  $staffRepository  職員リポジトリ
     */
    public function __construct(
        private StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * ログイン処理を実行
     *
     * @param  LoginRequest  $request  ログインリクエスト
     * @return StaffResponse 職員レスポンス
     *
     * @throws AuthenticationException 認証失敗時
     * @throws AccountLockedException アカウントロック時
     */
    public function execute(LoginRequest $request): StaffResponse
    {
        $email = Email::create($request->email);

        // 職員を検索
        $staff = $this->staffRepository->findByEmail($email);

        if ($staff === null) {
            throw AuthenticationException::invalidCredentials();
        }

        // アカウントロックチェック
        if ($staff->isLocked()) {
            throw new AccountLockedException(self::LOCK_DURATION_SECONDS);
        }

        // パスワード検証
        if (! $staff->verifyPassword($request->password)) {
            // 失敗カウントをインクリメント
            $staff->incrementFailedLoginAttempts();

            // 最大失敗回数に達したらロック
            if ($staff->failedLoginAttempts() >= self::MAX_FAILED_ATTEMPTS) {
                $staff->lock();
            }

            $this->staffRepository->save($staff);

            throw AuthenticationException::invalidCredentials();
        }

        // 成功時は失敗カウントをリセット
        $staff->resetFailedLoginAttempts();
        $this->staffRepository->save($staff);

        return StaffResponse::fromEntity($staff);
    }
}
