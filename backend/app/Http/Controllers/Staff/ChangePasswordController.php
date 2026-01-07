<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ChangePasswordRequest;
use App\Services\SecurityLogger;
use Illuminate\Http\JsonResponse;
use Packages\Domain\Staff\Application\UseCases\ChangePassword\ChangePasswordAction;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * パスワード変更コントローラー
 *
 * 認証済み職員のパスワード変更を処理する。
 *
 * @feature 001-security-preparation
 */
final class ChangePasswordController extends Controller
{
    /**
     * コンストラクタ
     *
     * @param  ChangePasswordAction  $action  パスワード変更アクション
     */
    public function __construct(
        private readonly ChangePasswordAction $action,
    ) {}

    /**
     * パスワード変更を実行
     *
     * @param  ChangePasswordRequest  $request  バリデーション済みリクエスト
     */
    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
        $user = $request->user();
        $staffId = StaffId::fromString($user->id);

        /** @var string $newPassword */
        $newPassword = $request->validated('new_password');
        $this->action->execute($staffId, $newPassword);

        // セキュリティログ: パスワード変更
        SecurityLogger::passwordChanged(
            staffId: $staffId->value(),
            ipAddress: $request->ip() ?? 'unknown'
        );

        return response()->json([
            'message' => __('messages.password_changed'),
        ]);
    }
}
