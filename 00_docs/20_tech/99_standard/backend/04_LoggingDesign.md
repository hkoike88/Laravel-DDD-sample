# バックエンド ログ設計標準

## 概要

本プロジェクトのバックエンドにおけるログ設計標準を定める。
運用監視、障害調査、セキュリティ監査に対応できるログ基盤を構築する。

---

## 基本方針

- **目的指向**: ログの利用目的を明確にし、必要な情報を過不足なく記録
- **構造化**: 機械可読な形式（JSON）で出力し、検索・分析を容易に
- **機密保護**: 個人情報・認証情報はログに出力しない
- **パフォーマンス**: ログ出力が本番システムの性能に影響を与えない
- **追跡可能性**: リクエスト単位でログを追跡できる仕組みを導入

---

## ログの分類

### ログチャンネル一覧

| チャンネル | 用途 | 保持期間 | ログレベル |
|-----------|------|---------|-----------|
| application | アプリケーション全般 | 14日 | debug 以上 |
| security | 認証・認可イベント | 90日 | info 以上 |
| audit | 業務操作の監査 | 1年 | info 以上 |
| error | エラー・例外 | 30日 | error 以上 |
| query | SQL クエリ（開発環境のみ） | 1日 | debug |
| performance | パフォーマンス計測 | 7日 | info 以上 |

---

## ログレベル定義

| レベル | 用途 | 例 |
|--------|------|-----|
| **emergency** | システム全停止 | データベース接続不可、ディスクフル |
| **alert** | 即座の対応が必要 | 外部API全断、キュー処理停止 |
| **critical** | 重大なエラー | 決済処理失敗、データ不整合検出 |
| **error** | 実行時エラー | 例外発生、バリデーションエラー |
| **warning** | 警告 | 非推奨API使用、リトライ発生 |
| **notice** | 正常だが重要なイベント | 設定変更、バッチ完了 |
| **info** | 一般的な情報 | リクエスト処理、ログイン成功 |
| **debug** | デバッグ情報 | 変数の値、処理フロー |

### 環境別ログレベル

| 環境 | 最小ログレベル |
|------|---------------|
| local | debug |
| staging | debug |
| production | info |

---

## ログフォーマット

### 構造化ログ（JSON）

```json
{
  "timestamp": "2025-12-25T10:30:00.123456+09:00",
  "level": "info",
  "channel": "application",
  "message": "注文が確定されました",
  "context": {
    "request_id": "01HXYZ123456789ABCDEF",
    "user_id": "01HXYZ987654321FEDCBA",
    "order_id": "01HXYZORDER12345",
    "action": "order.place",
    "duration_ms": 145
  },
  "extra": {
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "url": "/api/orders/01HXYZORDER12345/place",
    "method": "POST"
  }
}
```

### 必須フィールド

| フィールド | 説明 | 例 |
|-----------|------|-----|
| timestamp | ISO 8601 形式のタイムスタンプ | 2025-12-25T10:30:00.123456+09:00 |
| level | ログレベル | info, error, warning |
| channel | ログチャンネル | application, security |
| message | ログメッセージ | 注文が確定されました |
| context.request_id | リクエスト追跡ID | ULID 形式 |

### コンテキストフィールド

| フィールド | 説明 | 必須 |
|-----------|------|------|
| request_id | リクエスト追跡ID | 必須 |
| user_id | ユーザーID（認証済みの場合） | 条件付き |
| action | 実行されたアクション | 推奨 |
| duration_ms | 処理時間（ミリ秒） | 推奨 |
| resource_type | 対象リソースの種類 | 任意 |
| resource_id | 対象リソースのID | 任意 |

---

## Laravel ログ設定

### config/logging.php

```php
<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        // メインスタック
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'stderr'],
            'ignore_exceptions' => false,
        ],

        // アプリケーションログ
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        // セキュリティログ
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'info',
            'days' => 90,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        // 監査ログ
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 365,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        // エラーログ
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        // パフォーマンスログ
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 7,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        // 標準エラー出力（Docker/コンテナ向け）
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\StreamHandler::class,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        // 開発環境用 SQL ログ
        'query' => [
            'driver' => 'daily',
            'path' => storage_path('logs/query.log'),
            'level' => 'debug',
            'days' => 1,
        ],
    ],
];
```

---

## リクエスト追跡（Request ID）

### Middleware 実装

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * リクエスト追跡 Middleware
 *
 * 各リクエストに一意の ID を付与し、ログの追跡を可能にする
 */
final class RequestIdMiddleware
{
    private const REQUEST_ID_HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト ID を取得または生成
        $requestId = $request->header(self::REQUEST_ID_HEADER)
            ?? Str::ulid()->toBase32();

        // グローバルコンテキストに追加
        Log::shareContext([
            'request_id' => $requestId,
        ]);

        // リクエストに保存（後続処理で参照可能）
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // レスポンスヘッダーにも付与
        $response->headers->set(self::REQUEST_ID_HEADER, $requestId);

        return $response;
    }
}
```

### Kernel への登録

```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\RequestIdMiddleware::class,
];
```

---

## ログ出力パターン

### セキュリティログ

```php
<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * セキュリティイベントロガー
 *
 * 認証・認可関連のイベントを記録
 */
final class SecurityLogger
{
    /**
     * 認証イベントを記録
     */
    public function logAuthentication(
        ?string $userId,
        string $action,
        bool $success,
        ?string $reason = null,
    ): void {
        $level = $success ? 'info' : 'warning';

        Log::channel('security')->{$level}('Authentication event', [
            'user_id' => $userId,
            'action' => $action,
            'success' => $success,
            'reason' => $reason,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * ログイン成功を記録
     */
    public function logLoginSuccess(string $userId): void
    {
        $this->logAuthentication($userId, 'login', true);
    }

    /**
     * ログイン失敗を記録
     */
    public function logLoginFailure(string $email, string $reason): void
    {
        Log::channel('security')->warning('Login failed', [
            'email_hash' => hash('sha256', $email), // メールアドレスはハッシュ化
            'action' => 'login',
            'success' => false,
            'reason' => $reason,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * ログアウトを記録
     */
    public function logLogout(string $userId): void
    {
        $this->logAuthentication($userId, 'logout', true);
    }

    /**
     * アカウントロックを記録
     */
    public function logAccountLock(string $userId): void
    {
        Log::channel('security')->warning('Account locked', [
            'user_id' => $userId,
            'action' => 'account_lock',
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * 認可失敗を記録
     */
    public function logAuthorizationFailure(
        string $userId,
        string $resource,
        string $action,
    ): void {
        Log::channel('security')->warning('Authorization denied', [
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action,
            'ip' => Request::ip(),
        ]);
    }
}
```

### 監査ログ

```php
<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Support\Facades\Log;

/**
 * 監査ロガー
 *
 * 業務操作の証跡を記録
 */
final class AuditLogger
{
    /**
     * 業務操作を記録
     */
    public function log(
        string $userId,
        string $action,
        string $resourceType,
        string $resourceId,
        array $changes = [],
    ): void {
        Log::channel('audit')->info('Audit event', [
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changes' => $this->sanitizeChanges($changes),
        ]);
    }

    /**
     * リソース作成を記録
     */
    public function logCreate(
        string $userId,
        string $resourceType,
        string $resourceId,
        array $data = [],
    ): void {
        $this->log($userId, 'create', $resourceType, $resourceId, [
            'new' => $data,
        ]);
    }

    /**
     * リソース更新を記録
     */
    public function logUpdate(
        string $userId,
        string $resourceType,
        string $resourceId,
        array $before,
        array $after,
    ): void {
        $this->log($userId, 'update', $resourceType, $resourceId, [
            'before' => $before,
            'after' => $after,
        ]);
    }

    /**
     * リソース削除を記録
     */
    public function logDelete(
        string $userId,
        string $resourceType,
        string $resourceId,
    ): void {
        $this->log($userId, 'delete', $resourceType, $resourceId);
    }

    /**
     * 機密情報をマスキング
     */
    private function sanitizeChanges(array $changes): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'credit_card',
            'cvv',
        ];

        return $this->maskSensitiveData($changes, $sensitiveFields);
    }

    /**
     * 機密データをマスキング
     */
    private function maskSensitiveData(array $data, array $sensitiveFields): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields, true)) {
                $data[$key] = '[MASKED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value, $sensitiveFields);
            }
        }

        return $data;
    }
}
```

### パフォーマンスログ

```php
<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Support\Facades\Log;

/**
 * パフォーマンスロガー
 *
 * 処理時間やリソース使用量を記録
 */
final class PerformanceLogger
{
    /**
     * 処理時間を記録
     */
    public function logDuration(
        string $operation,
        float $durationMs,
        array $context = [],
    ): void {
        $level = $this->getDurationLevel($durationMs);

        Log::channel('performance')->{$level}('Operation completed', [
            'operation' => $operation,
            'duration_ms' => round($durationMs, 2),
            ...$context,
        ]);
    }

    /**
     * スロークエリを記録
     */
    public function logSlowQuery(
        string $sql,
        float $durationMs,
        array $bindings = [],
    ): void {
        Log::channel('performance')->warning('Slow query detected', [
            'sql' => $sql,
            'duration_ms' => round($durationMs, 2),
            'bindings_count' => count($bindings),
        ]);
    }

    /**
     * 外部API呼び出しを記録
     */
    public function logExternalApiCall(
        string $service,
        string $endpoint,
        float $durationMs,
        int $statusCode,
    ): void {
        $level = $statusCode >= 400 ? 'warning' : 'info';

        Log::channel('performance')->{$level}('External API call', [
            'service' => $service,
            'endpoint' => $endpoint,
            'duration_ms' => round($durationMs, 2),
            'status_code' => $statusCode,
        ]);
    }

    /**
     * 処理時間に応じたログレベルを決定
     */
    private function getDurationLevel(float $durationMs): string
    {
        return match (true) {
            $durationMs >= 5000 => 'warning',  // 5秒以上
            $durationMs >= 1000 => 'notice',   // 1秒以上
            default => 'info',
        };
    }
}
```

---

## アプリケーションログ

### UseCase でのログ出力

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\UseCases\Commands\Login;

use Illuminate\Support\Facades\Log;
use App\Logging\SecurityLogger;

final class LoginHandler
{
    public function __construct(
        private StaffRepository $staffRepository,
        private SecurityLogger $securityLogger,
    ) {}

    public function handle(LoginCommand $command): Staff
    {
        Log::info('Login attempt started', [
            'action' => 'staff.login.attempt',
        ]);

        $staff = $this->staffRepository->findByEmail(
            Email::fromString($command->email)
        );

        if ($staff === null) {
            $this->securityLogger->logLoginFailure(
                $command->email,
                'staff_not_found'
            );
            throw new InvalidCredentialsException();
        }

        if ($staff->isLocked()) {
            $this->securityLogger->logLoginFailure(
                $command->email,
                'account_locked'
            );
            throw new AccountLockedException();
        }

        if (!$staff->verifyPassword($command->password)) {
            $staff->recordLoginFailure();
            $this->staffRepository->save($staff);

            if ($staff->isLocked()) {
                $this->securityLogger->logAccountLock($staff->id()->value());
            }

            $this->securityLogger->logLoginFailure(
                $command->email,
                'invalid_password'
            );
            throw new InvalidCredentialsException();
        }

        $staff->recordLoginSuccess();
        $this->staffRepository->save($staff);

        $this->securityLogger->logLoginSuccess($staff->id()->value());

        Log::info('Login successful', [
            'action' => 'staff.login.success',
            'user_id' => $staff->id()->value(),
        ]);

        return $staff;
    }
}
```

### エラーハンドリングでのログ

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * ログに出力しない例外
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ];

    /**
     * 例外を報告
     */
    public function report(Throwable $e): void
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        Log::channel('error')->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->formatTrace($e),
        ]);
    }

    /**
     * スタックトレースをフォーマット
     */
    private function formatTrace(Throwable $e): array
    {
        return array_slice(
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
            10 // 最初の10フレームのみ
        );
    }
}
```

---

## 機密情報のマスキング

### マスキング対象

| カテゴリ | フィールド例 |
|---------|------------|
| 認証情報 | password, token, api_key, secret |
| 個人情報 | email, phone, address |
| 金融情報 | credit_card, cvv, account_number |
| セッション | session_id, csrf_token |

### グローバルマスキング設定

```php
<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Processor\ProcessorInterface;
use Monolog\LogRecord;

/**
 * 機密情報マスキングプロセッサ
 */
final class SensitiveDataProcessor implements ProcessorInterface
{
    /**
     * マスキング対象フィールド
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
        'authorization',
    ];

    /**
     * 部分マスキング対象フィールド
     */
    private const PARTIAL_MASK_FIELDS = [
        'email' => self::class . '::maskEmail',
        'phone' => self::class . '::maskPhone',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $this->maskSensitiveData($record->context);
        $extra = $this->maskSensitiveData($record->extra);

        return $record->with(context: $context, extra: $extra);
    }

    private function maskSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, self::SENSITIVE_FIELDS, true)) {
                $data[$key] = '[MASKED]';
            } elseif (isset(self::PARTIAL_MASK_FIELDS[$lowerKey])) {
                $data[$key] = call_user_func(
                    self::PARTIAL_MASK_FIELDS[$lowerKey],
                    $value
                );
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * メールアドレスをマスキング
     *
     * 例: test@example.com → t***@e***.com
     */
    private static function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return '[INVALID_EMAIL]';
        }

        [$local, $domain] = explode('@', $email);
        $maskedLocal = substr($local, 0, 1) . '***';

        $domainParts = explode('.', $domain);
        $maskedDomain = substr($domainParts[0], 0, 1) . '***.' . end($domainParts);

        return $maskedLocal . '@' . $maskedDomain;
    }

    /**
     * 電話番号をマスキング
     *
     * 例: 090-1234-5678 → 090-****-5678
     */
    private static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) < 4) {
            return '[MASKED]';
        }

        $first = substr($digits, 0, 3);
        $last = substr($digits, -4);

        return $first . '-****-' . $last;
    }
}
```

### プロセッサの登録

```php
// app/Providers/LoggingServiceProvider.php

namespace App\Providers;

use App\Logging\SensitiveDataProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 全チャンネルにプロセッサを追加
        foreach (['daily', 'security', 'audit', 'error', 'performance'] as $channel) {
            Log::channel($channel)->pushProcessor(new SensitiveDataProcessor());
        }
    }
}
```

---

## SQL クエリログ（開発環境）

### クエリログの有効化

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    if (config('app.env') === 'local') {
        $this->enableQueryLogging();
    }
}

private function enableQueryLogging(): void
{
    DB::listen(function ($query) {
        Log::channel('query')->debug('SQL Query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time_ms' => $query->time,
        ]);

        // スロークエリの検出（100ms以上）
        if ($query->time > 100) {
            app(PerformanceLogger::class)->logSlowQuery(
                $query->sql,
                $query->time,
                $query->bindings
            );
        }
    });
}
```

---

## ログ出力のベストプラクティス

### Do（推奨）

```php
// 構造化されたコンテキストを使用
Log::info('Order placed', [
    'order_id' => $order->id()->value(),
    'user_id' => $user->id()->value(),
    'total_amount' => $order->totalAmount()->toInt(),
    'item_count' => count($order->lines()),
]);

// アクション名を明確に
Log::info('Processing started', [
    'action' => 'order.process.start',
]);

// エラーには十分なコンテキストを
Log::error('Payment failed', [
    'order_id' => $orderId,
    'payment_method' => $paymentMethod,
    'error_code' => $e->getCode(),
    'error_message' => $e->getMessage(),
]);
```

### Don't（禁止）

```php
// 文字列連結でのログ出力
Log::info("Order {$orderId} placed by user {$userId}"); // NG

// 機密情報の出力
Log::info('Login attempt', [
    'email' => $email,      // NG: 平文
    'password' => $password, // NG: 絶対禁止
]);

// 巨大なデータの出力
Log::debug('Request data', [
    'body' => $request->all(), // NG: ファイルデータなどが含まれる可能性
]);

// 不必要な詳細
Log::debug('Entering method'); // NG: 意味のない情報
```

---

## ログローテーションと保持

### ファイルローテーション

| チャンネル | ローテーション | 保持期間 | 圧縮 |
|-----------|--------------|---------|------|
| application | 日次 | 14日 | gzip |
| security | 日次 | 90日 | gzip |
| audit | 日次 | 365日 | gzip |
| error | 日次 | 30日 | gzip |
| performance | 日次 | 7日 | gzip |
| query | 日次 | 1日 | なし |

### Logrotate 設定例

```bash
# /etc/logrotate.d/laravel

/var/www/app/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
    sharedscripts
    postrotate
        /usr/bin/pkill -USR1 php-fpm
    endscript
}

/var/www/app/storage/logs/security.log {
    daily
    missingok
    rotate 90
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
}

/var/www/app/storage/logs/audit.log {
    daily
    missingok
    rotate 365
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
}
```

---

## 監視・アラート

### アラート条件

| 条件 | 重要度 | 通知先 |
|------|--------|--------|
| error レベル以上が1分間に10件以上 | Critical | Slack + PagerDuty |
| 認証失敗が1分間に50件以上 | High | Slack |
| スロークエリ（5秒以上）発生 | Medium | Slack |
| ディスク使用率80%以上 | High | Slack |

### ログ監視設定例（Datadog）

```yaml
# datadog/conf.d/laravel.yaml
logs:
  - type: file
    path: /var/www/app/storage/logs/laravel-*.log
    service: library-system
    source: laravel
    sourcecategory: php

  - type: file
    path: /var/www/app/storage/logs/security-*.log
    service: library-system
    source: security
    sourcecategory: security

  - type: file
    path: /var/www/app/storage/logs/error-*.log
    service: library-system
    source: error
    sourcecategory: error
```

---

## チェックリスト

### 実装時

- [ ] リクエスト ID が全ログに含まれているか
- [ ] 機密情報がマスキングされているか
- [ ] ログレベルが適切か
- [ ] コンテキスト情報が十分か
- [ ] JSON 形式で出力されているか

### レビュー時

- [ ] パスワード・トークンなどがログに含まれていないか
- [ ] 個人情報が適切にマスキングされているか
- [ ] エラーログに十分なデバッグ情報があるか
- [ ] ログメッセージが明確か

### 運用時

- [ ] ログローテーションが機能しているか
- [ ] ディスク容量が十分か
- [ ] アラート設定が適切か
- [ ] ログの保持期間が要件を満たしているか

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計標準
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計標準
- [04_Non-FunctionalRequirements.md](./04_Non-FunctionalRequirements.md) - 非機能要件
