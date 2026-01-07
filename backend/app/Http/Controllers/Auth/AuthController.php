<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AbsoluteSessionTimeout;
use App\Http\Middleware\ConcurrentSessionLimit;
use App\Http\Requests\Auth\LoginFormRequest;
use App\Services\SecurityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Packages\Domain\Staff\Application\DTO\Auth\LoginRequest;
use Packages\Domain\Staff\Application\UseCases\Auth\GetCurrentUserUseCase;
use Packages\Domain\Staff\Application\UseCases\Auth\LoginUseCase;
use Packages\Domain\Staff\Domain\Exceptions\AccountLockedException;
use Packages\Domain\Staff\Domain\Exceptions\AuthenticationException;

/**
 * 認証コントローラー
 *
 * 職員認証（ログイン、ログアウト、認証状態確認）を処理する。
 */
class AuthController extends Controller
{
    /**
     * コンストラクタ
     *
     * @param  LoginUseCase  $loginUseCase  ログインユースケース
     * @param  GetCurrentUserUseCase  $getCurrentUserUseCase  認証済み職員取得ユースケース
     */
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly GetCurrentUserUseCase $getCurrentUserUseCase
    ) {}

    /**
     * ログイン処理
     *
     * @param  LoginFormRequest  $request  バリデーション済みリクエスト
     * @return JsonResponse 認証結果
     */
    public function login(LoginFormRequest $request): JsonResponse
    {
        try {
            /** @var array{email: string, password: string} $validated */
            $validated = $request->validated();

            $loginRequest = new LoginRequest(
                email: $validated['email'],
                password: $validated['password'],
            );

            $staffResponse = $this->loginUseCase->execute($loginRequest);

            // Laravel Auth によるセッション確立
            Auth::loginUsingId($staffResponse->id);

            // セッションが利用可能な場合のみ再生成（Sanctum SPA 認証時）
            if ($request->hasSession()) {
                $request->session()->regenerate();

                // 絶対タイムアウト用のセッション作成日時を記録
                AbsoluteSessionTimeout::setSessionCreatedAt($request);

                // 同時ログイン制限を適用（古いセッションを削除）
                ConcurrentSessionLimit::enforceLimit(
                    userId: $staffResponse->id,
                    isAdmin: $staffResponse->isAdmin,
                    currentSessionId: $request->session()->getId()
                );
            }

            // セキュリティログ: ログイン成功
            SecurityLogger::loginSuccess(
                staffId: $staffResponse->id,
                email: $validated['email'],
                ipAddress: $request->ip() ?? 'unknown',
                userAgent: $request->userAgent()
            );

            return response()->json([
                'data' => $staffResponse->toArray(),
            ]);
        } catch (AuthenticationException $e) {
            // セキュリティログ: ログイン失敗
            SecurityLogger::loginFailure(
                email: $validated['email'],
                ipAddress: $request->ip() ?? 'unknown',
                reason: 'invalid_credentials',
                userAgent: $request->userAgent()
            );

            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        } catch (AccountLockedException $e) {
            // セキュリティログ: アカウントロック（ログイン失敗として記録）
            SecurityLogger::loginFailure(
                email: $validated['email'],
                ipAddress: $request->ip() ?? 'unknown',
                reason: 'account_locked',
                userAgent: $request->userAgent()
            );

            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $e->retryAfterSeconds(),
            ], 423);
        }
    }

    /**
     * ログアウト処理
     *
     * @param  Request  $request  リクエスト
     * @return JsonResponse ログアウト結果
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        // セッションが利用可能な場合は無効化とトークン再生成
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'ログアウトしました',
        ]);
    }

    /**
     * 認証済み職員情報を取得
     *
     * @param  Request  $request  リクエスト
     * @return JsonResponse 職員情報
     */
    public function user(Request $request): JsonResponse
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
        $user = $request->user();

        $staffResponse = $this->getCurrentUserUseCase->execute($user->id);

        return response()->json([
            'data' => $staffResponse->toArray(),
        ]);
    }
}
