# バックエンド 外部連携設計標準

## 概要

本プロジェクトのバックエンドにおける外部システム連携（外部 API 呼び出し、Webhook 受信等）の設計標準を定める。
信頼性の高い外部連携を実現するため、タイムアウト、リトライ、サーキットブレーカー等のパターンを標準化する。

---

## 基本方針

- **障害耐性**: 外部システムの障害が自システムに波及しないよう設計
- **タイムアウト必須**: すべての外部呼び出しにタイムアウトを設定
- **リトライ戦略**: 一時的な障害に対して適切なリトライを実施
- **監視可能性**: 外部連携の状況をログ・メトリクスで可視化
- **セキュリティ**: 認証情報の安全な管理と通信の暗号化

---

## アーキテクチャ

### 外部連携の配置

外部 API クライアントは **Infrastructure 層** に配置する。

```
packages/Domain/{Context}/
├── Domain/
│   └── Services/
│       └── PaymentGatewayInterface.php   # インターフェース
├── Application/
│   └── UseCases/
│       └── ProcessPaymentHandler.php     # UseCase
└── Infrastructure/
    └── ExternalServices/
        └── StripePaymentGateway.php      # 実装
```

### インターフェース定義（Domain 層）

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Payment\Domain\Services;

use Packages\Domain\Payment\Domain\Model\PaymentResult;
use Packages\Domain\Payment\Domain\Model\PaymentRequest;

interface PaymentGatewayInterface
{
    /**
     * 決済を実行
     *
     * @throws PaymentFailedException
     * @throws PaymentGatewayUnavailableException
     */
    public function charge(PaymentRequest $request): PaymentResult;

    /**
     * 決済をキャンセル
     *
     * @throws PaymentNotFoundException
     * @throws PaymentGatewayUnavailableException
     */
    public function refund(string $paymentId, int $amount): PaymentResult;
}
```

### 実装クラス（Infrastructure 層）

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Payment\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Http;
use Packages\Domain\Payment\Domain\Services\PaymentGatewayInterface;

final class StripePaymentGateway implements PaymentGatewayInterface
{
    private const TIMEOUT_SECONDS = 10;
    private const RETRY_TIMES = 3;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    public function charge(PaymentRequest $request): PaymentResult
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(self::RETRY_TIMES, 100, $this->shouldRetry(...))
            ->post("{$this->baseUrl}/charges", [
                'amount' => $request->amount()->value(),
                'currency' => 'jpy',
                'source' => $request->token(),
            ]);

        if ($response->failed()) {
            throw new PaymentFailedException($response->json('error.message'));
        }

        return PaymentResult::fromApiResponse($response->json());
    }

    private function shouldRetry(\Exception $exception, $request): bool
    {
        // 5xx エラーのみリトライ
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            return $exception->response->serverError();
        }

        return false;
    }
}
```

---

## HTTP クライアント設計

### Laravel HTTP Client の使用

Laravel の HTTP Client（Guzzle ラッパー）を標準とする。

```php
use Illuminate\Support\Facades\Http;

// 基本的な GET リクエスト
$response = Http::get('https://api.example.com/users');

// POST リクエスト（JSON）
$response = Http::post('https://api.example.com/users', [
    'name' => '山田太郎',
    'email' => 'yamada@example.com',
]);

// レスポンスの処理
$data = $response->json();
$status = $response->status();
$success = $response->successful();  // 2xx
$failed = $response->failed();       // 4xx or 5xx
```

### クライアントクラスの設計

外部 API ごとにクライアントクラスを作成する。

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalServices;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ExternalApiClient
{
    private const DEFAULT_TIMEOUT = 10;
    private const DEFAULT_RETRY_TIMES = 3;
    private const DEFAULT_RETRY_DELAY = 100;  // ミリ秒

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
        private readonly int $retryTimes = self::DEFAULT_RETRY_TIMES,
    ) {}

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, ['json' => $data]);
    }

    public function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, ['json' => $data]);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, array $options = []): array
    {
        $url = $this->baseUrl . $path;
        $startTime = microtime(true);

        try {
            $response = $this->createRequest()
                ->{strtolower($method)}($url, $options['json'] ?? $options['query'] ?? []);

            $this->logRequest($method, $url, $response->status(), $startTime);

            if ($response->failed()) {
                throw new ExternalApiException(
                    "API request failed: {$response->status()}",
                    $response->status(),
                    $response->json()
                );
            }

            return $response->json() ?? [];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->logError($method, $url, $e, $startTime);
            throw new ExternalApiUnavailableException(
                '外部サービスに接続できません',
                previous: $e
            );
        }
    }

    private function createRequest(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->retry(
            $this->retryTimes,
            self::DEFAULT_RETRY_DELAY,
            fn ($exception) => $this->shouldRetry($exception)
        );
    }

    private function shouldRetry(\Exception $exception): bool
    {
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            $status = $exception->response->status();
            // 5xx エラーまたは 429 (Rate Limit) のみリトライ
            return $status >= 500 || $status === 429;
        }

        // 接続エラーはリトライ
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        return false;
    }

    private function logRequest(string $method, string $url, int $status, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('external')->info('External API request', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'duration_ms' => $duration,
        ]);
    }

    private function logError(string $method, string $url, \Exception $e, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('external')->error('External API error', [
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'duration_ms' => $duration,
        ]);
    }
}
```

---

## タイムアウト設定

### タイムアウトの種類

| 種類 | 説明 | 推奨値 |
|------|------|--------|
| 接続タイムアウト | サーバーへの接続確立までの時間 | 5秒 |
| 読み取りタイムアウト | レスポンス受信までの時間 | 10秒 |
| 合計タイムアウト | リクエスト全体の時間 | 30秒 |

### 設定例

```php
// 基本的なタイムアウト設定
$response = Http::timeout(10)->get($url);

// 接続タイムアウトと読み取りタイムアウトを個別に設定
$response = Http::connectTimeout(5)
    ->timeout(30)
    ->get($url);

// 用途別のタイムアウト設定
final class TimeoutConfig
{
    // 即座にレスポンスが必要な API
    public const REALTIME = 5;

    // 標準的な API
    public const STANDARD = 10;

    // 処理に時間がかかる API
    public const LONG_RUNNING = 30;

    // ファイルダウンロード等
    public const FILE_TRANSFER = 120;
}
```

### サービスプロバイダでの設定

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    // グローバルなデフォルトタイムアウト
    Http::macro('withDefaultTimeout', function () {
        return Http::timeout(10)->connectTimeout(5);
    });
}

// 使用例
$response = Http::withDefaultTimeout()->get($url);
```

---

## リトライ戦略

### 基本原則

1. **一時的なエラーのみリトライ**: 5xx エラー、タイムアウト、ネットワークエラー
2. **永続的なエラーはリトライしない**: 4xx エラー（認証エラー、バリデーションエラー等）
3. **指数バックオフ**: リトライ間隔を徐々に延長
4. **最大リトライ回数の設定**: 無限リトライを避ける
5. **冪等性の確認**: リトライしても安全な操作か確認

### リトライ可能なエラー

| ステータス | リトライ | 理由 |
|-----------|---------|------|
| 408 Request Timeout | ○ | 一時的なタイムアウト |
| 429 Too Many Requests | ○ | レート制限（Retry-After を参照） |
| 500 Internal Server Error | ○ | サーバー側の一時的な問題 |
| 502 Bad Gateway | ○ | プロキシ/ゲートウェイの問題 |
| 503 Service Unavailable | ○ | サービス一時停止 |
| 504 Gateway Timeout | ○ | ゲートウェイのタイムアウト |
| 400 Bad Request | × | リクエストが不正 |
| 401 Unauthorized | × | 認証エラー |
| 403 Forbidden | × | 権限エラー |
| 404 Not Found | × | リソースが存在しない |
| 422 Unprocessable Entity | × | バリデーションエラー |

### 実装例

```php
// 基本的なリトライ
$response = Http::retry(3, 100)->get($url);

// 指数バックオフ付きリトライ
$response = Http::retry(3, function (int $attempt) {
    // 100ms, 200ms, 400ms...
    return 100 * pow(2, $attempt - 1);
})->get($url);

// 条件付きリトライ
$response = Http::retry(
    times: 3,
    sleepMilliseconds: 100,
    when: function (\Exception $exception) {
        if (!$exception instanceof \Illuminate\Http\Client\RequestException) {
            return true;  // 接続エラーはリトライ
        }

        $status = $exception->response->status();

        // リトライ可能なステータスコード
        return in_array($status, [408, 429, 500, 502, 503, 504], true);
    },
    throw: true
)->get($url);
```

### 冪等性の確保

リトライ時に重複処理を防ぐため、冪等キーを使用する。

```php
final class IdempotentApiClient
{
    public function createOrder(array $data, string $idempotencyKey): array
    {
        return Http::withHeaders([
            'Idempotency-Key' => $idempotencyKey,
        ])
        ->retry(3, 100)
        ->post("{$this->baseUrl}/orders", $data)
        ->json();
    }
}

// 使用例
$idempotencyKey = 'order-' . $orderId . '-' . time();
$result = $client->createOrder($orderData, $idempotencyKey);
```

---

## サーキットブレーカー

### 概念

外部サービスの障害時に、連続した失敗を検知してリクエストを一時的に遮断する。

```
[Closed] → 失敗が閾値を超える → [Open]
                                    ↓
                              一定時間経過
                                    ↓
[Closed] ← 成功 ← [Half-Open] → 失敗 → [Open]
```

| 状態 | 説明 |
|------|------|
| Closed | 通常状態。リクエストを通す |
| Open | 遮断状態。リクエストを即座に拒否 |
| Half-Open | 試行状態。一部のリクエストを通して回復を確認 |

### 実装例

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\CircuitBreaker;

use Illuminate\Support\Facades\Cache;

final class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private readonly string $serviceName,
        private readonly int $failureThreshold = 5,      // 失敗閾値
        private readonly int $recoveryTimeout = 30,       // 回復待機時間（秒）
        private readonly int $halfOpenMaxAttempts = 3,    // Half-Open での試行回数
    ) {}

    /**
     * サーキットブレーカーを通してリクエストを実行
     *
     * @throws CircuitOpenException
     */
    public function call(callable $action): mixed
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            if (!$this->shouldAttemptRecovery()) {
                throw new CircuitOpenException(
                    "Circuit is open for service: {$this->serviceName}"
                );
            }
            $this->transitionTo(self::STATE_HALF_OPEN);
        }

        try {
            $result = $action();
            $this->recordSuccess();
            return $result;

        } catch (\Exception $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    /**
     * 成功を記録
     */
    private function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successCount = Cache::increment($this->cacheKey('half_open_success'));

            if ($successCount >= $this->halfOpenMaxAttempts) {
                $this->transitionTo(self::STATE_CLOSED);
            }
        } else {
            // Closed 状態では失敗カウントをリセット
            Cache::forget($this->cacheKey('failure_count'));
        }
    }

    /**
     * 失敗を記録
     */
    private function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Half-Open で失敗したら即座に Open へ
            $this->transitionTo(self::STATE_OPEN);
            return;
        }

        $failureCount = Cache::increment($this->cacheKey('failure_count'));

        if ($failureCount >= $this->failureThreshold) {
            $this->transitionTo(self::STATE_OPEN);
        }
    }

    /**
     * 状態を遷移
     */
    private function transitionTo(string $newState): void
    {
        Cache::put($this->cacheKey('state'), $newState, now()->addHours(1));

        if ($newState === self::STATE_OPEN) {
            Cache::put(
                $this->cacheKey('opened_at'),
                now()->timestamp,
                now()->addHours(1)
            );
            Cache::forget($this->cacheKey('failure_count'));
        }

        if ($newState === self::STATE_HALF_OPEN) {
            Cache::forget($this->cacheKey('half_open_success'));
        }

        if ($newState === self::STATE_CLOSED) {
            Cache::forget($this->cacheKey('failure_count'));
            Cache::forget($this->cacheKey('opened_at'));
            Cache::forget($this->cacheKey('half_open_success'));
        }
    }

    /**
     * 回復を試行すべきか
     */
    private function shouldAttemptRecovery(): bool
    {
        $openedAt = Cache::get($this->cacheKey('opened_at'));

        if ($openedAt === null) {
            return true;
        }

        return (now()->timestamp - $openedAt) >= $this->recoveryTimeout;
    }

    private function getState(): string
    {
        return Cache::get($this->cacheKey('state'), self::STATE_CLOSED);
    }

    private function cacheKey(string $key): string
    {
        return "circuit_breaker:{$this->serviceName}:{$key}";
    }

    /**
     * 現在の状態を取得（監視用）
     */
    public function getStatus(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failure_count' => Cache::get($this->cacheKey('failure_count'), 0),
            'opened_at' => Cache::get($this->cacheKey('opened_at')),
        ];
    }
}
```

### 使用例

```php
final class PaymentGatewayWithCircuitBreaker implements PaymentGatewayInterface
{
    public function __construct(
        private readonly StripePaymentGateway $gateway,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    public function charge(PaymentRequest $request): PaymentResult
    {
        return $this->circuitBreaker->call(
            fn () => $this->gateway->charge($request)
        );
    }
}

// サービスプロバイダでの登録
$this->app->singleton(PaymentGatewayInterface::class, function ($app) {
    $gateway = new StripePaymentGateway(
        config('services.stripe.key'),
        config('services.stripe.url')
    );

    $circuitBreaker = new CircuitBreaker(
        serviceName: 'stripe',
        failureThreshold: 5,
        recoveryTimeout: 30
    );

    return new PaymentGatewayWithCircuitBreaker($gateway, $circuitBreaker);
});
```

---

## Webhook 設計

### Webhook 受信の基本構成

```php
// routes/api.php
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->middleware('webhook.verify:stripe')
    ->name('webhooks.stripe');
```

### コントローラ実装

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? 'unknown';

        Log::channel('webhook')->info('Webhook received', [
            'provider' => 'stripe',
            'event_type' => $eventType,
            'event_id' => $payload['id'] ?? null,
        ]);

        try {
            $this->processEvent($eventType, $payload);

            return response()->json(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::channel('webhook')->error('Webhook processing failed', [
                'provider' => 'stripe',
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            // 500 を返すと Webhook が再送される
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function processEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($payload),
            'payment_intent.failed' => $this->handlePaymentFailed($payload),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($payload),
            default => Log::channel('webhook')->warning('Unhandled webhook event', [
                'event_type' => $eventType,
            ]),
        };
    }

    private function handlePaymentSucceeded(array $payload): void
    {
        // 決済成功時の処理
        dispatch(new ProcessPaymentSuccessJob($payload['data']['object']));
    }

    private function handlePaymentFailed(array $payload): void
    {
        // 決済失敗時の処理
        dispatch(new ProcessPaymentFailureJob($payload['data']['object']));
    }

    private function handleSubscriptionUpdated(array $payload): void
    {
        // サブスクリプション更新時の処理
        dispatch(new ProcessSubscriptionUpdateJob($payload['data']['object']));
    }
}
```

### 署名検証ミドルウェア

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $isValid = match ($provider) {
            'stripe' => $this->verifyStripeSignature($request),
            'github' => $this->verifyGithubSignature($request),
            default => false,
        };

        if (!$isValid) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }

    private function verifyStripeSignature(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();
        $secret = config('services.stripe.webhook_secret');

        if (empty($signature) || empty($secret)) {
            return false;
        }

        try {
            // Stripe SDK を使用する場合
            \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return false;
        }
    }

    private function verifyGithubSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $secret = config('services.github.webhook_secret');

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
```

### 冪等性の確保

同じ Webhook が複数回送信される可能性があるため、冪等性を確保する。

```php
<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Cache;

final class WebhookIdempotencyGuard
{
    private const TTL_HOURS = 24;

    /**
     * イベントが処理済みかチェックし、未処理なら処理済みとしてマーク
     */
    public function checkAndMark(string $eventId): bool
    {
        $key = "webhook:processed:{$eventId}";

        // すでに処理済みの場合は false を返す
        if (Cache::has($key)) {
            return false;
        }

        // 処理済みとしてマーク
        Cache::put($key, true, now()->addHours(self::TTL_HOURS));

        return true;
    }
}

// コントローラでの使用
public function handle(Request $request): JsonResponse
{
    $eventId = $request->input('id');

    if (!$this->idempotencyGuard->checkAndMark($eventId)) {
        Log::channel('webhook')->info('Duplicate webhook ignored', [
            'event_id' => $eventId,
        ]);
        return response()->json(['status' => 'already_processed']);
    }

    // 処理を続行...
}
```

### Webhook 送信

自システムから Webhook を送信する場合。

```php
<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WebhookDispatcher
{
    private const TIMEOUT_SECONDS = 10;
    private const MAX_RETRIES = 3;

    public function dispatch(string $url, array $payload, string $secret): bool
    {
        $signature = $this->generateSignature($payload, $secret);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => now()->timestamp,
            ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(self::MAX_RETRIES, 1000)
            ->post($url, $payload);

            $success = $response->successful();

            Log::channel('webhook')->info('Webhook dispatched', [
                'url' => $url,
                'status' => $response->status(),
                'success' => $success,
            ]);

            return $success;

        } catch (\Exception $e) {
            Log::channel('webhook')->error('Webhook dispatch failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function generateSignature(array $payload, string $secret): string
    {
        $body = json_encode($payload);
        return hash_hmac('sha256', $body, $secret);
    }
}
```

---

## 認証・認可

### API キーの管理

```php
// config/services.php
return [
    'stripe' => [
        'key' => env('STRIPE_API_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'external_api' => [
        'base_url' => env('EXTERNAL_API_URL'),
        'api_key' => env('EXTERNAL_API_KEY'),
        'timeout' => env('EXTERNAL_API_TIMEOUT', 10),
    ],
];
```

### OAuth 2.0 クライアント

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class OAuthApiClient
{
    private const TOKEN_CACHE_KEY = 'oauth_token';
    private const TOKEN_BUFFER_SECONDS = 60;  // 有効期限前に更新

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $tokenUrl,
    ) {}

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, ['json' => $data]);
    }

    private function request(string $method, string $path, array $options = []): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->timeout(10)
            ->{strtolower($method)}($this->baseUrl . $path, $options['json'] ?? $options['query'] ?? []);

        if ($response->status() === 401) {
            // トークンが無効な場合は再取得してリトライ
            Cache::forget(self::TOKEN_CACHE_KEY);
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->timeout(10)
                ->{strtolower($method)}($this->baseUrl . $path, $options['json'] ?? $options['query'] ?? []);
        }

        return $response->json() ?? [];
    }

    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3600, function () {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->failed()) {
                throw new OAuthTokenException('Failed to obtain access token');
            }

            $data = $response->json();
            $expiresIn = $data['expires_in'] ?? 3600;

            // 有効期限より少し前にキャッシュを切らす
            Cache::put(
                self::TOKEN_CACHE_KEY,
                $data['access_token'],
                now()->addSeconds($expiresIn - self::TOKEN_BUFFER_SECONDS)
            );

            return $data['access_token'];
        });
    }
}
```

---

## ログ・監視

### ログ設定

```php
// config/logging.php
'channels' => [
    'external' => [
        'driver' => 'daily',
        'path' => storage_path('logs/external.log'),
        'level' => 'info',
        'days' => 30,
    ],

    'webhook' => [
        'driver' => 'daily',
        'path' => storage_path('logs/webhook.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

### ログ項目

**外部 API 呼び出し時:**

| 項目 | 説明 |
|------|------|
| service | サービス名（stripe, github 等） |
| method | HTTP メソッド |
| url | リクエスト URL |
| status | レスポンスステータス |
| duration_ms | 処理時間（ミリ秒） |
| retry_count | リトライ回数 |
| error | エラーメッセージ（失敗時） |

```php
Log::channel('external')->info('External API request', [
    'service' => 'stripe',
    'method' => 'POST',
    'url' => 'https://api.stripe.com/v1/charges',
    'status' => 200,
    'duration_ms' => 245.5,
    'retry_count' => 0,
]);
```

**Webhook 受信時:**

| 項目 | 説明 |
|------|------|
| provider | プロバイダ名 |
| event_type | イベント種別 |
| event_id | イベント ID |
| processing_time_ms | 処理時間 |
| status | 処理結果（processed, failed, ignored） |

```php
Log::channel('webhook')->info('Webhook processed', [
    'provider' => 'stripe',
    'event_type' => 'payment_intent.succeeded',
    'event_id' => 'evt_1234567890',
    'processing_time_ms' => 120,
    'status' => 'processed',
]);
```

### メトリクス

監視すべきメトリクス：

| メトリクス | 説明 | アラート閾値 |
|-----------|------|-------------|
| external_api_requests_total | リクエスト総数 | - |
| external_api_request_duration | レスポンス時間 | 95%ile > 5秒 |
| external_api_errors_total | エラー数 | > 10/分 |
| external_api_retry_total | リトライ数 | > 50/分 |
| circuit_breaker_state | サーキットブレーカー状態 | Open 状態が 5分以上 |
| webhook_received_total | Webhook 受信数 | - |
| webhook_processing_errors | Webhook 処理エラー | > 5/分 |

---

## エラーハンドリング

### 例外クラス

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\ExternalService;

use RuntimeException;

/**
 * 外部サービスが利用不可
 */
class ExternalServiceUnavailableException extends RuntimeException
{
    public function __construct(
        string $serviceName,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        $msg = $message ?: "{$serviceName} サービスに接続できません";
        parent::__construct($msg, 503, $previous);
    }
}

/**
 * 外部 API エラー
 */
class ExternalApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly ?array $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}

/**
 * サーキットブレーカーが Open 状態
 */
class CircuitOpenException extends RuntimeException
{
    public function __construct(string $message = 'Circuit breaker is open')
    {
        parent::__construct($message, 503);
    }
}

/**
 * レート制限超過
 */
class RateLimitExceededException extends RuntimeException
{
    public function __construct(
        private readonly int $retryAfter = 60,
        string $message = 'Rate limit exceeded'
    ) {
        parent::__construct($message, 429);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
```

### 例外ハンドラでの処理

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e): Response
{
    if ($e instanceof ExternalServiceUnavailableException) {
        return response()->json([
            'error' => [
                'code' => 'SERVICE_UNAVAILABLE',
                'message' => '外部サービスが一時的に利用できません。しばらく経ってから再度お試しください。',
            ],
        ], 503);
    }

    if ($e instanceof CircuitOpenException) {
        return response()->json([
            'error' => [
                'code' => 'SERVICE_UNAVAILABLE',
                'message' => 'サービスが一時的に利用できません。しばらく経ってから再度お試しください。',
            ],
        ], 503);
    }

    if ($e instanceof RateLimitExceededException) {
        return response()->json([
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'リクエスト回数の上限に達しました。しばらく経ってから再度お試しください。',
            ],
        ], 429)->header('Retry-After', $e->getRetryAfter());
    }

    return parent::render($request, $e);
}
```

---

## テスト

### HTTP クライアントのモック

```php
use Illuminate\Support\Facades\Http;

public function test_外部APIからデータを取得できる(): void
{
    Http::fake([
        'api.example.com/users/*' => Http::response([
            'id' => 1,
            'name' => '山田太郎',
        ], 200),
    ]);

    $client = new ExternalApiClient('https://api.example.com', 'test-key');
    $result = $client->get('/users/1');

    $this->assertEquals('山田太郎', $result['name']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com/users/1'
            && $request->hasHeader('Authorization', 'Bearer test-key');
    });
}

public function test_タイムアウト時にリトライする(): void
{
    Http::fake([
        'api.example.com/*' => Http::sequence()
            ->pushStatus(500)
            ->pushStatus(500)
            ->push(['success' => true], 200),
    ]);

    $client = new ExternalApiClient('https://api.example.com', 'test-key');
    $result = $client->get('/data');

    $this->assertTrue($result['success']);

    // 3回リクエストされたことを確認
    Http::assertSentCount(3);
}
```

### サーキットブレーカーのテスト

```php
public function test_閾値を超えるとサーキットがOpenになる(): void
{
    $circuitBreaker = new CircuitBreaker(
        serviceName: 'test',
        failureThreshold: 3,
        recoveryTimeout: 30
    );

    // 3回失敗させる
    for ($i = 0; $i < 3; $i++) {
        try {
            $circuitBreaker->call(function () {
                throw new \Exception('Error');
            });
        } catch (\Exception $e) {
            // 期待通り
        }
    }

    // 4回目は CircuitOpenException
    $this->expectException(CircuitOpenException::class);
    $circuitBreaker->call(fn () => 'success');
}
```

---

## チェックリスト

### 外部 API 呼び出し

- [ ] タイムアウトが設定されているか
- [ ] リトライ戦略が実装されているか
- [ ] リトライ対象のエラーが適切に判定されているか
- [ ] サーキットブレーカーが必要な場合に実装されているか
- [ ] 認証情報が安全に管理されているか（環境変数）
- [ ] ログが出力されているか
- [ ] エラー時の処理が実装されているか

### Webhook

- [ ] 署名検証が実装されているか
- [ ] 冪等性が確保されているか
- [ ] 非同期処理（Job）を使用しているか
- [ ] ログが出力されているか
- [ ] 失敗時のリトライに対応しているか

### セキュリティ

- [ ] HTTPS を使用しているか
- [ ] API キーが環境変数で管理されているか
- [ ] 署名検証を行っているか
- [ ] 機密情報がログに出力されていないか

---

## 関連ドキュメント

- [01_ArchitectureDesign](../../20_architecture/backend/01_ArchitectureDesign/) - アーキテクチャ設計標準
- [02_SecurityDesign.md](./02_SecurityDesign.md) - セキュリティ設計
- [03_Non-FunctionalRequirements.md](./03_Non-FunctionalRequirements.md) - 非機能要件
- [04_LoggingDesign.md](./04_LoggingDesign.md) - ログ設計標準
- [07_ErrorHandling.md](./07_ErrorHandling.md) - エラーハンドリング設計
- [12_EventDrivenDesign.md](./12_EventDrivenDesign.md) - イベント駆動設計標準

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-25 | 初版作成 |
