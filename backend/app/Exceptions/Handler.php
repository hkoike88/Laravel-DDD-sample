<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Packages\Domain\Book\Domain\Exceptions\BookNotFoundException;
use Packages\Domain\Staff\Domain\Exceptions\DuplicateEmailException;
use Packages\Domain\Staff\Domain\Exceptions\LastAdminProtectionException;
use Packages\Domain\Staff\Domain\Exceptions\OptimisticLockException;
use Packages\Domain\Staff\Domain\Exceptions\SelfRoleChangeException;
use Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * 例外ハンドラー
 *
 * アプリケーション全体の例外を一元的に処理する。
 * - ログ記録
 * - HTTP レスポンスへの変換
 * - エラーレスポンス形式の統一
 */
class Handler extends ExceptionHandler
{
    /**
     * ログに記録しない例外
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
        NotFoundHttpException::class,
    ];

    /**
     * レスポンスに含めない入力フィールド
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
    ];

    /**
     * 例外を報告
     *
     * 親クラスのデフォルト処理を使用。
     * $dontReport に設定された例外以外は自動的にログに記録される。
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * 例外をレスポンスに変換
     *
     * API リクエスト（JSON を期待する、または /api/* パス）の場合、
     * JSON レスポンスを返す。
     */
    public function render($request, Throwable $e): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderJsonResponse($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * JSON レスポンスを生成
     *
     * 例外タイプに応じて適切な HTTP ステータスコードとエラーレスポンスを返す。
     */
    private function renderJsonResponse(Request $request, Throwable $e): JsonResponse
    {
        $response = match (true) {
            // ドメイン例外（個別対応）
            $e instanceof StaffNotFoundException => $this->renderStaffNotFoundException($e),
            $e instanceof BookNotFoundException => $this->renderBookNotFoundException($e),
            $e instanceof DuplicateEmailException => $this->renderDuplicateEmailException($e),
            $e instanceof OptimisticLockException => $this->renderOptimisticLockException($e),
            $e instanceof SelfRoleChangeException => $this->renderSelfRoleChangeException($e),
            $e instanceof LastAdminProtectionException => $this->renderLastAdminProtectionException($e),

            // Laravel バリデーション例外
            $e instanceof ValidationException => $this->renderValidationException($e),

            // Laravel 認証例外
            $e instanceof AuthenticationException => $this->renderAuthenticationException($e),

            // Eloquent モデル未発見
            $e instanceof ModelNotFoundException => $this->renderModelNotFoundException($e),

            // 404
            $e instanceof NotFoundHttpException => $this->renderNotFoundHttpException($e),

            // レート制限
            $e instanceof TooManyRequestsHttpException => $this->renderTooManyRequestsException($e),

            // その他の HTTP 例外
            $e instanceof HttpExceptionInterface => $this->renderHttpException($e),

            // その他（システムエラー）
            default => $this->renderInternalServerError($e),
        };

        return $response;
    }

    /**
     * 職員未発見例外のレスポンス
     */
    private function renderStaffNotFoundException(StaffNotFoundException $e): JsonResponse
    {
        return response()->json([
            'message' => '指定された職員が見つかりません',
        ], 404);
    }

    /**
     * 蔵書未発見例外のレスポンス
     */
    private function renderBookNotFoundException(BookNotFoundException $e): JsonResponse
    {
        return response()->json([
            'message' => '指定された蔵書が見つかりません',
        ], 404);
    }

    /**
     * メールアドレス重複例外のレスポンス
     */
    private function renderDuplicateEmailException(DuplicateEmailException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => '入力内容に誤りがあります',
                'details' => [
                    [
                        'field' => 'email',
                        'code' => 'DUPLICATE_EMAIL',
                        'message' => 'このメールアドレスは既に登録されています',
                    ],
                ],
            ],
        ], 422);
    }

    /**
     * 楽観的ロック例外のレスポンス
     */
    private function renderOptimisticLockException(OptimisticLockException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
        ], 409);
    }

    /**
     * 自分の権限変更例外のレスポンス
     */
    private function renderSelfRoleChangeException(SelfRoleChangeException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'code' => 'SELF_ROLE_CHANGE',
        ], 422);
    }

    /**
     * 最後の管理者保護例外のレスポンス
     */
    private function renderLastAdminProtectionException(LastAdminProtectionException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'code' => 'LAST_ADMIN_PROTECTION',
        ], 422);
    }


    /**
     * バリデーション例外のレスポンス
     */
    private function renderValidationException(ValidationException $e): JsonResponse
    {
        $details = [];
        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $details[] = [
                    'field' => $field,
                    'code' => 'VALIDATION_ERROR',
                    'message' => $message,
                ];
            }
        }

        return response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => '入力内容に誤りがあります',
                'details' => $details,
            ],
        ], 422);
    }

    /**
     * 認証例外のレスポンス
     */
    private function renderAuthenticationException(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'AUTH_UNAUTHENTICATED',
                'message' => '認証が必要です',
            ],
        ], 401);
    }

    /**
     * モデル未発見例外のレスポンス
     *
     * @param ModelNotFoundException<\Illuminate\Database\Eloquent\Model> $e
     */
    private function renderModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = $e->getModel() ? class_basename($e->getModel()) : 'Unknown';

        return response()->json([
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => '指定されたリソースが見つかりません',
                'details' => [
                    'resource_type' => $model,
                    'resource_ids' => $e->getIds(),
                ],
            ],
        ], 404);
    }

    /**
     * 404 例外のレスポンス
     */
    private function renderNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => '指定されたリソースが見つかりません',
            ],
        ], 404);
    }

    /**
     * レート制限例外のレスポンス
     */
    private function renderTooManyRequestsException(TooManyRequestsHttpException $e): JsonResponse
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

        return response()->json([
            'error' => [
                'code' => 'SYSTEM_RATE_LIMIT_EXCEEDED',
                'message' => 'リクエスト制限を超えました。しばらく経ってから再度お試しください',
                'details' => [
                    'retry_after' => (int) $retryAfter,
                ],
            ],
        ], 429);
    }

    /**
     * HTTP 例外のレスポンス
     */
    protected function renderHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();
        $code = $this->getErrorCodeFromStatusCode($statusCode);
        $message = $this->getMessageFromStatusCode($statusCode);

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }

    /**
     * 内部サーバーエラーのレスポンス
     */
    private function renderInternalServerError(Throwable $e): JsonResponse
    {
        $data = [
            'error' => [
                'code' => 'SYSTEM_INTERNAL_ERROR',
                'message' => 'システムエラーが発生しました。しばらく経ってから再度お試しください',
            ],
        ];

        if (config('app.debug')) {
            $data['error']['debug'] = $this->getDebugInfo($e);
        }

        return response()->json($data, 500);
    }

    /**
     * デバッグ情報を取得
     *
     * @return array<string, mixed>
     */
    private function getDebugInfo(Throwable $e): array
    {
        return [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(
                array_map(
                    fn ($frame) => [
                        'file' => $frame['file'] ?? 'unknown',
                        'line' => $frame['line'] ?? 0,
                        'function' => $frame['function'],
                        'class' => $frame['class'] ?? null,
                    ],
                    $e->getTrace()
                ),
                0,
                10
            ),
        ];
    }

    /**
     * ステータスコードからエラーコードを取得
     */
    private function getErrorCodeFromStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'BUSINESS_RULE_VIOLATION',
            401 => 'AUTH_UNAUTHENTICATED',
            403 => 'AUTHZ_PERMISSION_DENIED',
            404 => 'RESOURCE_NOT_FOUND',
            405 => 'SYSTEM_METHOD_NOT_ALLOWED',
            409 => 'RESOURCE_CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'SYSTEM_RATE_LIMIT_EXCEEDED',
            500 => 'SYSTEM_INTERNAL_ERROR',
            502 => 'SYSTEM_EXTERNAL_SERVICE_ERROR',
            503 => 'SYSTEM_SERVICE_UNAVAILABLE',
            504 => 'SYSTEM_TIMEOUT',
            default => 'SYSTEM_INTERNAL_ERROR',
        };
    }

    /**
     * ステータスコードからメッセージを取得
     */
    private function getMessageFromStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'リクエストが不正です',
            401 => '認証が必要です',
            403 => 'この操作を実行する権限がありません',
            404 => '指定されたリソースが見つかりません',
            405 => 'このメソッドは許可されていません',
            409 => 'リソースが競合しています',
            422 => '入力内容に誤りがあります',
            429 => 'リクエスト制限を超えました',
            500 => 'システムエラーが発生しました',
            502 => '外部サービスとの通信に失敗しました',
            503 => 'サービスが一時的に利用できません',
            504 => 'リクエストがタイムアウトしました',
            default => 'エラーが発生しました',
        };
    }
}
