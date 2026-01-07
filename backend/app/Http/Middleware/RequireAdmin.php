<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理者権限チェックミドルウェア
 *
 * 管理者権限を持つ職員のみがアクセス可能なルートを保護する。
 * 認証済みで is_admin が true の場合のみリクエストを通過させ、
 * それ以外の場合は 403 Forbidden を返す。
 *
 * @feature 003-role-based-menu
 */
class RequireAdmin
{
    /**
     * リクエストを処理
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord|null $user */
        $user = $request->user();

        // 未認証またはユーザー情報なしの場合（認証ミドルウェアでキャッチされるはずだが念のため）
        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => '認証が必要です',
                ],
            ], 401);
        }

        // 管理者権限チェック
        if (! $user->is_admin) {
            return response()->json([
                'error' => [
                    'code' => 'AUTHZ_PERMISSION_DENIED',
                    'message' => 'この操作を行う権限がありません',
                ],
            ], 403);
        }

        return $next($request);
    }
}
