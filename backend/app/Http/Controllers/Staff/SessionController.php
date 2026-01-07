<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\Domain\Staff\Application\UseCases\Session\GetActiveSessionsAction;
use Packages\Domain\Staff\Application\UseCases\Session\TerminateOtherSessionsAction;
use Packages\Domain\Staff\Application\UseCases\Session\TerminateSessionAction;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;

/**
 * セッション管理コントローラー
 *
 * 認証済み職員のセッション管理（一覧、終了）を処理する。
 *
 * @feature 001-security-preparation
 */
final class SessionController extends Controller
{
    /**
     * コンストラクタ
     *
     * @param  GetActiveSessionsAction  $getActiveSessions  セッション取得アクション
     * @param  TerminateSessionAction  $terminateSession  セッション終了アクション
     * @param  TerminateOtherSessionsAction  $terminateOtherSessions  他セッション終了アクション
     */
    public function __construct(
        private readonly GetActiveSessionsAction $getActiveSessions,
        private readonly TerminateSessionAction $terminateSession,
        private readonly TerminateOtherSessionsAction $terminateOtherSessions,
    ) {}

    /**
     * アクティブなセッション一覧を取得
     *
     * @param  Request  $request  リクエスト
     * @return JsonResponse セッション一覧
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
        $user = $request->user();
        $staffId = StaffId::fromString($user->id);
        $currentSessionId = $request->session()->getId();

        $sessions = $this->getActiveSessions->execute($staffId, $currentSessionId);

        return response()->json([
            'data' => $sessions,
        ]);
    }

    /**
     * 指定したセッションを終了
     *
     * @param  Request  $request  リクエスト
     * @param  string  $id  セッションID
     * @return JsonResponse 結果
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
        $user = $request->user();
        $staffId = StaffId::fromString($user->id);

        // 現在のセッションは終了できない
        if ($id === $request->session()->getId()) {
            return response()->json([
                'message' => '現在のセッションは終了できません。ログアウトを使用してください。',
            ], 400);
        }

        $success = $this->terminateSession->execute($staffId, $id);

        if (! $success) {
            return response()->json([
                'message' => 'セッションが見つかりません。',
            ], 404);
        }

        // セキュリティログ: セッション終了
        SecurityLogger::sessionTerminated(
            staffId: $staffId->value(),
            sessionId: $id,
            terminatedBy: 'self'
        );

        return response()->json([
            'message' => 'セッションを終了しました。',
        ]);
    }

    /**
     * 現在のセッション以外を全て終了
     *
     * @param  Request  $request  リクエスト
     * @return JsonResponse 結果
     */
    public function destroyOthers(Request $request): JsonResponse
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $user */
        $user = $request->user();
        $staffId = StaffId::fromString($user->id);
        $currentSessionId = $request->session()->getId();

        $count = $this->terminateOtherSessions->execute($staffId, $currentSessionId);

        // セキュリティログ: 他セッション一括終了
        if ($count > 0) {
            SecurityLogger::sessionTerminatedOthers(
                staffId: $staffId->value(),
                count: $count
            );
        }

        return response()->json([
            'message' => "{$count}件のセッションを終了しました。",
            'count' => $count,
        ]);
    }
}
