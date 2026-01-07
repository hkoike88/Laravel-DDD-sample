# バックエンド エラーハンドリング設計標準

## 概要

本プロジェクトのバックエンドにおけるエラーハンドリング設計標準を定める。
一貫性のあるエラー処理により、デバッグ効率とユーザー体験を向上させる。

---

## 基本方針

- **一貫性**: エラーコード体系とレスポンス形式を統一
- **階層化**: 例外クラスをレイヤーごとに分離
- **情報の適切な開示**: 環境に応じてデバッグ情報を制御
- **ユーザーフレンドリー**: エラーメッセージは日本語で分かりやすく
- **ログ連携**: エラー発生時は適切なログを記録

---

## エラーコード体系

### コード形式

```
{カテゴリ}_{サブカテゴリ}_{詳細}
```

**例:**
- `AUTH_INVALID_CREDENTIALS` - 認証情報が不正
- `VALIDATION_REQUIRED_FIELD` - 必須フィールド未入力
- `BUSINESS_BOOK_NOT_AVAILABLE` - 書籍が利用不可

### カテゴリ一覧

| カテゴリ | プレフィックス | 説明 | HTTPステータス |
|---------|---------------|------|---------------|
| 認証 | `AUTH_` | 認証関連のエラー | 401 |
| 認可 | `AUTHZ_` | 権限関連のエラー | 403 |
| バリデーション | `VALIDATION_` | 入力検証エラー | 422 |
| リソース | `RESOURCE_` | リソース関連エラー | 404, 409 |
| ビジネス | `BUSINESS_` | ビジネスルール違反 | 400 |
| システム | `SYSTEM_` | システムエラー | 500, 502, 503 |

### エラーコード一覧

#### 認証エラー（AUTH_）

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `AUTH_UNAUTHENTICATED` | 認証が必要 | 401 |
| `AUTH_INVALID_CREDENTIALS` | 認証情報が不正 | 401 |
| `AUTH_TOKEN_EXPIRED` | トークン期限切れ | 401 |
| `AUTH_TOKEN_INVALID` | トークンが無効 | 401 |
| `AUTH_ACCOUNT_LOCKED` | アカウントがロック中 | 401 |
| `AUTH_ACCOUNT_DISABLED` | アカウントが無効 | 401 |
| `AUTH_SESSION_EXPIRED` | セッション期限切れ | 401 |

#### 認可エラー（AUTHZ_）

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `AUTHZ_PERMISSION_DENIED` | 権限なし | 403 |
| `AUTHZ_ROLE_REQUIRED` | 必要なロールがない | 403 |
| `AUTHZ_RESOURCE_FORBIDDEN` | リソースへのアクセス禁止 | 403 |
| `AUTHZ_ACTION_FORBIDDEN` | 操作が禁止されている | 403 |

#### バリデーションエラー（VALIDATION_）

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `VALIDATION_ERROR` | バリデーションエラー（汎用） | 422 |
| `VALIDATION_REQUIRED_FIELD` | 必須フィールド未入力 | 422 |
| `VALIDATION_INVALID_FORMAT` | フォーマット不正 | 422 |
| `VALIDATION_MAX_LENGTH` | 最大長超過 | 422 |
| `VALIDATION_MIN_LENGTH` | 最小長未満 | 422 |
| `VALIDATION_INVALID_EMAIL` | メール形式不正 | 422 |
| `VALIDATION_INVALID_DATE` | 日付形式不正 | 422 |
| `VALIDATION_OUT_OF_RANGE` | 範囲外の値 | 422 |

#### リソースエラー（RESOURCE_）

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `RESOURCE_NOT_FOUND` | リソースが見つからない | 404 |
| `RESOURCE_ALREADY_EXISTS` | リソースが既に存在 | 409 |
| `RESOURCE_CONFLICT` | リソースの競合 | 409 |
| `RESOURCE_DELETED` | リソースが削除済み | 410 |
| `RESOURCE_LOCKED` | リソースがロック中 | 423 |

#### ビジネスエラー（BUSINESS_）

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `BUSINESS_RULE_VIOLATION` | ビジネスルール違反（汎用） | 400 |
| `BUSINESS_BOOK_NOT_AVAILABLE` | 書籍が利用不可 | 400 |
| `BUSINESS_LOAN_LIMIT_EXCEEDED` | 貸出上限超過 | 400 |
| `BUSINESS_RESERVATION_LIMIT_EXCEEDED` | 予約上限超過 | 400 |
| `BUSINESS_ALREADY_RETURNED` | 既に返却済み | 400 |
| `BUSINESS_OVERDUE_EXISTS` | 延滞中の貸出あり | 400 |
| `BUSINESS_INVALID_STATUS_TRANSITION` | 無効な状態遷移 | 400 |

#### システムエラー（SYSTEM_）

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `SYSTEM_INTERNAL_ERROR` | 内部エラー | 500 |
| `SYSTEM_DATABASE_ERROR` | データベースエラー | 500 |
| `SYSTEM_EXTERNAL_SERVICE_ERROR` | 外部サービスエラー | 502 |
| `SYSTEM_SERVICE_UNAVAILABLE` | サービス停止中 | 503 |
| `SYSTEM_TIMEOUT` | タイムアウト | 504 |
| `SYSTEM_RATE_LIMIT_EXCEEDED` | レート制限超過 | 429 |

---

## 例外クラス設計

### 階層構造

```
Exception (PHP 標準)
├── ApplicationException (アプリケーション基底)
│   ├── DomainException (ドメイン層)
│   │   ├── InvalidCredentialsException
│   │   ├── AccountLockedException
│   │   ├── BusinessRuleException
│   │   └── InvalidStatusTransitionException
│   ├── ApplicationLayerException (アプリケーション層)
│   │   ├── ValidationException (Laravel 標準を使用)
│   │   └── AuthorizationException
│   └── InfrastructureException (インフラ層)
│       ├── ResourceNotFoundException
│       ├── ResourceConflictException
│       ├── ExternalServiceException
│       └── DatabaseException
```

### 基底例外クラス

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Exceptions;

/**
 * アプリケーション例外の基底クラス
 *
 * すべてのカスタム例外はこのクラスを継承する
 */
abstract class ApplicationException extends \Exception
{
    /**
     * エラーコードを取得
     */
    abstract public function getErrorCode(): string;

    /**
     * HTTP ステータスコードを取得
     */
    abstract public function getHttpStatusCode(): int;

    /**
     * ユーザー向けメッセージを取得
     */
    public function getUserMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * 追加のエラー詳細を取得
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return [];
    }

    /**
     * ログに記録すべきかどうか
     */
    public function shouldReport(): bool
    {
        return true;
    }

    /**
     * ログレベルを取得
     */
    public function getLogLevel(): string
    {
        return 'error';
    }
}
```

### Domain 例外

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Domain\Exceptions;

use Packages\Common\Exceptions\ApplicationException;

/**
 * ドメイン例外の基底クラス
 *
 * ビジネスルール違反を表す
 */
abstract class DomainException extends ApplicationException
{
    public function getHttpStatusCode(): int
    {
        return 400;
    }

    public function getLogLevel(): string
    {
        return 'warning';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

use Packages\Common\Domain\Exceptions\DomainException;

/**
 * 認証情報不正例外
 */
final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('メールアドレスまたはパスワードが正しくありません');
    }

    public function getErrorCode(): string
    {
        return 'AUTH_INVALID_CREDENTIALS';
    }

    public function getHttpStatusCode(): int
    {
        return 401;
    }

    public function shouldReport(): bool
    {
        return false; // 認証失敗は通常のログで記録
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

use Packages\Common\Domain\Exceptions\DomainException;

/**
 * アカウントロック例外
 */
final class AccountLockedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('アカウントがロックされています。管理者にお問い合わせください');
    }

    public function getErrorCode(): string
    {
        return 'AUTH_ACCOUNT_LOCKED';
    }

    public function getHttpStatusCode(): int
    {
        return 401;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Exceptions;

use Packages\Common\Domain\Exceptions\DomainException;

/**
 * 書籍利用不可例外
 */
final class BookNotAvailableException extends DomainException
{
    public function __construct(string $bookId)
    {
        parent::__construct('この書籍は現在利用できません');
        $this->bookId = $bookId;
    }

    private string $bookId;

    public function getErrorCode(): string
    {
        return 'BUSINESS_BOOK_NOT_AVAILABLE';
    }

    public function getDetails(): array
    {
        return [
            'book_id' => $this->bookId,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Domain\Exceptions;

/**
 * 無効な状態遷移例外
 */
final class InvalidStatusTransitionException extends DomainException
{
    public function __construct(
        private string $currentStatus,
        private string $targetStatus,
        string $message = '無効な状態遷移です',
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'BUSINESS_INVALID_STATUS_TRANSITION';
    }

    public function getDetails(): array
    {
        return [
            'current_status' => $this->currentStatus,
            'target_status' => $this->targetStatus,
        ];
    }
}
```

### Infrastructure 例外

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Infrastructure\Exceptions;

use Packages\Common\Exceptions\ApplicationException;

/**
 * インフラストラクチャ例外の基底クラス
 *
 * 技術的な問題を表す
 */
abstract class InfrastructureException extends ApplicationException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Infrastructure\Exceptions;

/**
 * リソース未発見例外
 */
final class ResourceNotFoundException extends InfrastructureException
{
    public function __construct(
        private string $resourceType,
        private string $resourceId,
    ) {
        parent::__construct("指定された{$this->getResourceLabel()}が見つかりません");
    }

    public function getErrorCode(): string
    {
        return 'RESOURCE_NOT_FOUND';
    }

    public function getHttpStatusCode(): int
    {
        return 404;
    }

    public function getDetails(): array
    {
        return [
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
        ];
    }

    public function shouldReport(): bool
    {
        return false;
    }

    public function getLogLevel(): string
    {
        return 'info';
    }

    private function getResourceLabel(): string
    {
        return match ($this->resourceType) {
            'book' => '書籍',
            'user' => '利用者',
            'staff' => '職員',
            'loan' => '貸出',
            'reservation' => '予約',
            default => 'リソース',
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Infrastructure\Exceptions;

/**
 * リソース競合例外
 */
final class ResourceConflictException extends InfrastructureException
{
    public function __construct(
        private string $resourceType,
        private string $field,
        private string $value,
    ) {
        parent::__construct('このリソースは既に存在します');
    }

    public function getErrorCode(): string
    {
        return 'RESOURCE_ALREADY_EXISTS';
    }

    public function getHttpStatusCode(): int
    {
        return 409;
    }

    public function getDetails(): array
    {
        return [
            'resource_type' => $this->resourceType,
            'field' => $this->field,
        ];
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Packages\Common\Infrastructure\Exceptions;

/**
 * 外部サービス例外
 */
final class ExternalServiceException extends InfrastructureException
{
    public function __construct(
        private string $serviceName,
        string $message,
        private ?int $serviceStatusCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return 'SYSTEM_EXTERNAL_SERVICE_ERROR';
    }

    public function getHttpStatusCode(): int
    {
        return 502;
    }

    public function getDetails(): array
    {
        return [
            'service' => $this->serviceName,
            'service_status_code' => $this->serviceStatusCode,
        ];
    }

    public function getUserMessage(): string
    {
        return '外部サービスとの通信に失敗しました。しばらく経ってから再度お試しください';
    }
}
```

---

## エラーレスポンス形式

### 基本形式

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "ユーザー向けエラーメッセージ",
    "details": []
  }
}
```

### フィールド定義

| フィールド | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| error.code | string | Yes | エラーコード |
| error.message | string | Yes | ユーザー向けメッセージ |
| error.details | array | No | 詳細情報（バリデーションエラー等） |
| error.debug | object | No | デバッグ情報（開発環境のみ） |

### バリデーションエラー

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": [
      {
        "field": "email",
        "code": "VALIDATION_REQUIRED_FIELD",
        "message": "メールアドレスは必須です"
      },
      {
        "field": "password",
        "code": "VALIDATION_MIN_LENGTH",
        "message": "パスワードは12文字以上で入力してください",
        "params": {
          "min": 12
        }
      }
    ]
  }
}
```

### ビジネスエラー

```json
{
  "error": {
    "code": "BUSINESS_LOAN_LIMIT_EXCEEDED",
    "message": "貸出上限に達しています。返却後に再度お試しください",
    "details": [
      {
        "current_loans": 5,
        "max_loans": 5
      }
    ]
  }
}
```

### システムエラー（本番環境）

```json
{
  "error": {
    "code": "SYSTEM_INTERNAL_ERROR",
    "message": "システムエラーが発生しました。しばらく経ってから再度お試しください"
  }
}
```

### システムエラー（開発環境）

```json
{
  "error": {
    "code": "SYSTEM_DATABASE_ERROR",
    "message": "システムエラーが発生しました",
    "debug": {
      "exception": "PDOException",
      "message": "SQLSTATE[42S02]: Base table or view not found",
      "file": "/app/packages/Domain/Book/Infrastructure/EloquentBookRepository.php",
      "line": 42,
      "trace": [
        {
          "file": "/app/packages/Domain/Book/Application/UseCases/GetBookHandler.php",
          "line": 25,
          "function": "find",
          "class": "EloquentBookRepository"
        }
      ]
    }
  }
}
```

---

## 例外ハンドラー実装

### Handler クラス

```php
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
use Packages\Common\Exceptions\ApplicationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

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
     */
    public function report(Throwable $e): void
    {
        if ($e instanceof ApplicationException) {
            if ($e->shouldReport()) {
                Log::channel('error')->log($e->getLogLevel(), $e->getMessage(), [
                    'code' => $e->getErrorCode(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'details' => $e->getDetails(),
                ]);
            }
            return;
        }

        parent::report($e);
    }

    /**
     * 例外をレスポンスに変換
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
     */
    private function renderJsonResponse(Request $request, Throwable $e): JsonResponse
    {
        $response = match (true) {
            // アプリケーション例外
            $e instanceof ApplicationException => $this->renderApplicationException($e),

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
            $e instanceof HttpException => $this->renderHttpException($e),

            // その他（システムエラー）
            default => $this->renderInternalServerError($e),
        };

        return $response;
    }

    /**
     * アプリケーション例外のレスポンス
     */
    private function renderApplicationException(ApplicationException $e): JsonResponse
    {
        $data = [
            'error' => [
                'code' => $e->getErrorCode(),
                'message' => $e->getUserMessage(),
            ],
        ];

        $details = $e->getDetails();
        if (!empty($details)) {
            $data['error']['details'] = $details;
        }

        if (config('app.debug')) {
            $data['error']['debug'] = $this->getDebugInfo($e);
        }

        return response()->json($data, $e->getHttpStatusCode());
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
     */
    private function renderModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());

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
    private function renderHttpException(HttpException $e): JsonResponse
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
                    fn($frame) => [
                        'file' => $frame['file'] ?? 'unknown',
                        'line' => $frame['line'] ?? 0,
                        'function' => $frame['function'] ?? 'unknown',
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
```

---

## エラーメッセージ設計

### 多言語対応

```php
// resources/lang/ja/errors.php
return [
    // 認証
    'auth' => [
        'unauthenticated' => '認証が必要です',
        'invalid_credentials' => 'メールアドレスまたはパスワードが正しくありません',
        'account_locked' => 'アカウントがロックされています。管理者にお問い合わせください',
        'token_expired' => 'セッションの有効期限が切れました。再度ログインしてください',
    ],

    // 認可
    'authz' => [
        'permission_denied' => 'この操作を実行する権限がありません',
    ],

    // バリデーション
    'validation' => [
        'error' => '入力内容に誤りがあります',
        'required' => ':attribute は必須です',
        'email' => '有効なメールアドレスを入力してください',
        'min_length' => ':attribute は :min 文字以上で入力してください',
        'max_length' => ':attribute は :max 文字以内で入力してください',
    ],

    // リソース
    'resource' => [
        'not_found' => '指定されたリソースが見つかりません',
        'already_exists' => 'このリソースは既に存在します',
        'conflict' => 'リソースが競合しています',
    ],

    // ビジネス
    'business' => [
        'book_not_available' => 'この書籍は現在利用できません',
        'loan_limit_exceeded' => '貸出上限に達しています。返却後に再度お試しください',
        'reservation_limit_exceeded' => '予約上限に達しています',
        'already_returned' => 'この貸出は既に返却済みです',
        'overdue_exists' => '延滞中の貸出があります。返却後に再度お試しください',
    ],

    // システム
    'system' => [
        'internal_error' => 'システムエラーが発生しました。しばらく経ってから再度お試しください',
        'external_service_error' => '外部サービスとの通信に失敗しました',
        'service_unavailable' => 'サービスが一時的に利用できません',
        'rate_limit_exceeded' => 'リクエスト制限を超えました。しばらく経ってから再度お試しください',
    ],
];
```

### メッセージ取得ヘルパー

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * エラーメッセージヘルパー
 */
final class ErrorMessageHelper
{
    /**
     * エラーコードからメッセージを取得
     */
    public static function getMessage(string $errorCode, array $params = []): string
    {
        $key = self::convertCodeToTranslationKey($errorCode);
        $message = __("errors.{$key}", $params);

        // 翻訳が見つからない場合はデフォルトメッセージ
        if ($message === "errors.{$key}") {
            return __('errors.system.internal_error');
        }

        return $message;
    }

    /**
     * エラーコードを翻訳キーに変換
     *
     * AUTH_INVALID_CREDENTIALS → auth.invalid_credentials
     */
    private static function convertCodeToTranslationKey(string $code): string
    {
        $parts = explode('_', strtolower($code), 2);

        if (count($parts) !== 2) {
            return 'system.internal_error';
        }

        return $parts[0] . '.' . $parts[1];
    }
}
```

---

## ログ連携

### エラーログ設定

```php
// config/logging.php
'channels' => [
    'error' => [
        'driver' => 'daily',
        'path' => storage_path('logs/error.log'),
        'level' => 'error',
        'days' => 30,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
],
```

### エラーログ出力

```php
// 例外ハンドラーでの出力（上記 Handler クラス参照）
Log::channel('error')->log($e->getLogLevel(), $e->getMessage(), [
    'code' => $e->getErrorCode(),
    'exception' => get_class($e),
    'file' => $e->getFile(),
    'line' => $e->getLine(),
    'details' => $e->getDetails(),
    'request_id' => request()->header('X-Request-Id'),
    'user_id' => auth()->id(),
    'url' => request()->fullUrl(),
    'method' => request()->method(),
]);
```

### ログ出力例

```json
{
  "timestamp": "2025-12-25T10:30:00.123456+09:00",
  "level": "error",
  "message": "SQLSTATE[42S02]: Base table or view not found",
  "context": {
    "code": "SYSTEM_DATABASE_ERROR",
    "exception": "PDOException",
    "file": "/app/packages/Domain/Book/Infrastructure/EloquentBookRepository.php",
    "line": 42,
    "details": {},
    "request_id": "01HXYZ123456789ABCDEF",
    "user_id": "01HXYZUSER12345",
    "url": "https://api.example.com/api/v1/books/123",
    "method": "GET"
  }
}
```

---

## 環境別設定

### 設定値

| 設定 | 開発環境 | ステージング | 本番環境 |
|------|---------|-------------|---------|
| APP_DEBUG | true | false | false |
| デバッグ情報 | 表示 | 非表示 | 非表示 |
| スタックトレース | 表示 | 非表示 | 非表示 |
| 詳細エラーメッセージ | 表示 | 非表示 | 非表示 |

### デバッグ情報の制御

```php
// 例外ハンドラー内
if (config('app.debug')) {
    $data['error']['debug'] = $this->getDebugInfo($e);
}
```

---

## UseCase での例外処理

### 例外をスローするパターン

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\Login;

use Packages\Domain\Staff\Domain\Exceptions\AccountLockedException;
use Packages\Domain\Staff\Domain\Exceptions\InvalidCredentialsException;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;

final class LoginHandler
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
    ) {}

    /**
     * ログイン処理を実行
     *
     * @throws InvalidCredentialsException 認証情報が不正な場合
     * @throws AccountLockedException アカウントがロックされている場合
     */
    public function handle(LoginCommand $command): Staff
    {
        $staff = $this->staffRepository->findByEmail($command->email);

        if ($staff === null) {
            throw new InvalidCredentialsException();
        }

        if ($staff->isLocked()) {
            throw new AccountLockedException();
        }

        if (!$staff->verifyPassword($command->password)) {
            $staff->recordLoginFailure();
            $this->staffRepository->save($staff);

            throw new InvalidCredentialsException();
        }

        $staff->recordLoginSuccess();
        $this->staffRepository->save($staff);

        return $staff;
    }
}
```

### Controller での例外処理

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\StaffResource;
use Illuminate\Http\JsonResponse;
use Packages\Domain\Staff\Application\UseCases\Commands\Login\LoginCommand;
use Packages\Domain\Staff\Application\UseCases\Commands\Login\LoginHandler;

final class AuthController extends Controller
{
    public function __construct(
        private LoginHandler $loginHandler,
    ) {}

    /**
     * ログイン
     *
     * 例外は Handler で自動的に処理される
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $command = new LoginCommand(
            email: $request->input('email'),
            password: $request->input('password'),
        );

        $staff = $this->loginHandler->handle($command);

        // セッション開始
        $request->session()->regenerate();
        auth()->login($staff->toEloquentModel());

        return response()->json([
            'data' => new StaffResource($staff),
        ]);
    }
}
```

---

## チェックリスト

### 設計時

- [ ] エラーコードがカテゴリ体系に従っているか
- [ ] 例外クラスが適切なレイヤーに配置されているか
- [ ] ユーザー向けメッセージが分かりやすいか
- [ ] HTTPステータスコードが適切か

### 実装時

- [ ] 例外クラスが ApplicationException を継承しているか
- [ ] getErrorCode() が定義されているか
- [ ] getHttpStatusCode() が定義されているか
- [ ] shouldReport() で適切にログ出力を制御しているか
- [ ] PHPDoc で @throws を記載しているか

### レビュー時

- [ ] 機密情報がエラーレスポンスに含まれていないか
- [ ] 本番環境でデバッグ情報が非表示になるか
- [ ] エラーメッセージが多言語対応しているか
- [ ] ログに十分な情報が記録されるか

---

## 関連ドキュメント

- [01_CodingStandards.md](./01_CodingStandards.md) - コーディング規約（例外処理）
- [02_SecurityDesign.md](./02_SecurityDesign.md) - セキュリティ設計
- [04_LoggingDesign.md](./04_LoggingDesign.md) - ログ設計標準
- [05_ApiDesign.md](./05_ApiDesign.md) - API 設計標準（エラーレスポンス）
- [09_TransactionDesign.md](./09_TransactionDesign.md) - トランザクション設計標準
- [10_TransactionConsistencyDesign.md](./10_TransactionConsistencyDesign.md) - トランザクション整合性保証設計
