# バックエンド 非機能要件

## 概要

本プロジェクトのバックエンドにおける非機能要件を定める。
パフォーマンス、可用性、運用性、監視などの基準と実装方針を記載する。

---

## 要件サマリー

| 分類 | 要件 | 目標値 |
|------|------|--------|
| パフォーマンス | API レスポンス時間 | 95%ile < 200ms |
| パフォーマンス | スループット | 1,000 req/sec |
| 可用性 | 稼働率 | 99.9%（月間ダウンタイム < 43分） |
| 可用性 | 計画メンテナンス | 月1回、深夜帯 |
| スケーラビリティ | 同時接続数 | 10,000 |
| 運用性 | デプロイ頻度 | 週1回以上 |
| 運用性 | デプロイ時間 | < 10分 |

---

## パフォーマンス

### レスポンス時間目標

| エンドポイント種別 | 95%ile | 99%ile |
|-------------------|--------|--------|
| 参照系 API（一覧） | < 200ms | < 500ms |
| 参照系 API（詳細） | < 100ms | < 200ms |
| 更新系 API | < 300ms | < 500ms |
| バッチ処理 | 処理件数に応じて設定 | - |

### データベース最適化

#### インデックス設計

```php
// マイグレーションでのインデックス設定
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained();
    $table->string('status', 20);
    $table->integer('amount');
    $table->timestamps();

    // 検索パターンに応じたインデックス
    $table->index('customer_id');
    $table->index('status');
    $table->index(['customer_id', 'status']);  // 複合インデックス
    $table->index('created_at');
});
```

#### クエリ最適化

```php
// ✓ Good: N+1 問題の回避
$orders = OrderRecord::with(['customer', 'lines'])->get();

// ✓ Good: 必要なカラムのみ取得
$orders = OrderRecord::select(['id', 'status', 'amount'])->get();

// ✓ Good: チャンク処理（大量データ）
OrderRecord::chunk(1000, function ($orders) {
    foreach ($orders as $order) {
        // 処理
    }
});

// ✓ Good: カーソル（メモリ効率）
foreach (OrderRecord::cursor() as $order) {
    // 処理
}
```

#### スロークエリの検出

```php
// config/database.php
'mysql' => [
    // ...
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
],

// AppServiceProvider.php（開発環境のみ）
if (config('app.debug')) {
    DB::listen(function ($query) {
        if ($query->time > 100) {  // 100ms 以上
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        }
    });
}
```

### キャッシュ戦略

#### キャッシュレイヤー

```
[クライアント] → [CDN] → [Redis] → [アプリ] → [DB]
                   ↑         ↑
              静的資産   セッション/
                        データキャッシュ
```

#### キャッシュ設定

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

#### キャッシュ実装パターン

```php
// Repository でのキャッシュ
final class CachedOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EloquentOrderRepository $repository,
        private CacheInterface $cache,
    ) {}

    public function find(OrderId $id): Order
    {
        $key = "order:{$id->value()}";

        return $this->cache->remember($key, 3600, function () use ($id) {
            return $this->repository->find($id);
        });
    }

    public function save(Order $order): void
    {
        $this->repository->save($order);
        $this->cache->forget("order:{$order->id()->value()}");
    }
}
```

#### キャッシュ TTL 基準

| データ種別 | TTL | 理由 |
|-----------|-----|------|
| マスタデータ | 24時間 | 更新頻度が低い |
| ユーザーセッション | 2時間 | セキュリティ考慮 |
| API レスポンス | 5分 | 適度な鮮度 |
| 集計データ | 1時間 | 計算コスト削減 |

### 非同期処理

#### Queue 設定

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

#### Job の実装

```php
final class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;  // リトライ間隔（秒）
    public int $timeout = 30;  // タイムアウト（秒）

    public function __construct(
        private int $orderId,
    ) {}

    public function handle(OrderNotificationService $service): void
    {
        $service->sendConfirmation(OrderId::from($this->orderId));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Order confirmation failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

#### Queue ワーカー設定

```bash
# 本番環境での起動
php artisan queue:work redis --sleep=3 --tries=3 --max-jobs=1000 --max-time=3600
```

---

## 可用性

### 稼働率目標

| レベル | 稼働率 | 年間ダウンタイム | 月間ダウンタイム |
|--------|--------|-----------------|-----------------|
| 目標 | 99.9% | 8.76時間 | 43分 |
| 最低 | 99.5% | 43.8時間 | 3.6時間 |

### 冗長構成

```
                    [ロードバランサー]
                    /              \
            [App Server 1]    [App Server 2]
                    \              /
                    [Redis Cluster]
                    /              \
            [DB Primary]  ←→  [DB Replica]
```

### ヘルスチェック

```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => $this->checkDatabase(),
        'redis' => $this->checkRedis(),
        'storage' => $this->checkStorage(),
    ];

    $healthy = collect($checks)->every(fn ($status) => $status === 'ok');

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});
```

```php
// app/Http/Controllers/HealthController.php
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            // DB 接続チェック
            DB::connection()->getPdo();

            // Redis 接続チェック
            Cache::store('redis')->get('health_check');

            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ], 503);
        }
    }
}
```

### グレースフルシャットダウン

```php
// シグナルハンドリング（Queue ワーカー）
pcntl_signal(SIGTERM, function () {
    // 現在のジョブ完了を待機
    $this->shouldQuit = true;
});
```

### サーキットブレーカー

```php
// 外部 API 呼び出し時のサーキットブレーカー
final class ExternalApiClient
{
    private int $failureCount = 0;
    private int $threshold = 5;
    private ?Carbon $lastFailure = null;

    public function call(string $endpoint): Response
    {
        if ($this->isOpen()) {
            throw new CircuitOpenException('Circuit is open');
        }

        try {
            $response = Http::timeout(5)->get($endpoint);
            $this->reset();
            return $response;
        } catch (\Exception $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    private function isOpen(): bool
    {
        if ($this->failureCount >= $this->threshold) {
            // 30秒後にハーフオープン
            if ($this->lastFailure->diffInSeconds(now()) < 30) {
                return true;
            }
        }
        return false;
    }
}
```

---

## スケーラビリティ

### 水平スケーリング対応

#### ステートレス設計

```php
// セッションは Redis に保存
// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),

// ファイルはクラウドストレージに保存
// config/filesystems.php
'default' => env('FILESYSTEM_DISK', 's3'),
```

#### 分散ロック

```php
// 排他制御が必要な処理
$lock = Cache::lock('process-order-' . $orderId, 10);

if ($lock->get()) {
    try {
        // 排他処理
    } finally {
        $lock->release();
    }
} else {
    throw new LockTimeoutException('処理中です');
}
```

### データベーススケーリング

#### リードレプリカ

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1'),
            env('DB_READ_HOST_2'),
        ],
    ],
    'write' => [
        'host' => env('DB_WRITE_HOST'),
    ],
    'sticky' => true,  // 書き込み後は同一接続を使用
],
```

#### クエリでの使い分け

```php
// 参照系は自動的にリードレプリカへ
$orders = OrderRecord::where('status', 'placed')->get();

// 書き込み直後の参照はプライマリへ
$order = OrderRecord::create([...]);
$order->refresh();  // sticky: true により同一接続
```

---

## 運用性

### ログ設計

#### ログレベル

| レベル | 用途 | 例 |
|--------|------|-----|
| emergency | システム停止レベル | DB 接続不可 |
| alert | 即座の対応が必要 | ディスク容量逼迫 |
| critical | 重大なエラー | 決済処理失敗 |
| error | エラー | API エラー |
| warning | 警告 | 非推奨機能の使用 |
| notice | 重要な情報 | ユーザー登録 |
| info | 一般情報 | API リクエスト |
| debug | デバッグ情報 | 詳細なトレース |

#### 構造化ログ

```php
// ログフォーマット
Log::info('Order placed', [
    'order_id' => $order->id()->value(),
    'customer_id' => $order->customerId()->value(),
    'amount' => $order->amount()->toInt(),
    'request_id' => request()->header('X-Request-ID'),
    'user_id' => auth()->id(),
    'timestamp' => now()->toIso8601String(),
]);
```

#### ログ設定

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'stderr'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],

    'stderr' => [
        'driver' => 'monolog',
        'level' => env('LOG_LEVEL', 'debug'),
        'handler' => StreamHandler::class,
        'formatter' => JsonFormatter::class,  // JSON 形式
        'with' => [
            'stream' => 'php://stderr',
        ],
    ],
],
```

### 監視

#### メトリクス収集

| メトリクス | 収集間隔 | アラート閾値 |
|-----------|----------|-------------|
| CPU 使用率 | 1分 | > 80% が 5分継続 |
| メモリ使用率 | 1分 | > 85% |
| ディスク使用率 | 5分 | > 80% |
| API レスポンス時間 | リクエストごと | 95%ile > 500ms |
| エラーレート | 1分 | > 1% |
| Queue 滞留数 | 1分 | > 1000 |

#### アプリケーションメトリクス

```php
// Prometheus 形式でのメトリクス出力
Route::get('/metrics', function () {
    $metrics = [
        'http_requests_total' => Counter::get('http_requests'),
        'http_request_duration_seconds' => Histogram::get('http_duration'),
        'queue_jobs_pending' => Gauge::get('queue_pending'),
        'database_connections' => Gauge::get('db_connections'),
    ];

    return response($this->formatPrometheus($metrics))
        ->header('Content-Type', 'text/plain');
});
```

#### エラー通知

```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if ($this->shouldReport($e)) {
            // Slack 通知
            Notification::route('slack', config('services.slack.webhook'))
                ->notify(new ExceptionOccurred($e));
        }
    });
}
```

### デプロイ

#### デプロイ手順

```bash
# 1. メンテナンスモード
php artisan down --secret="bypass-token"

# 2. コード取得
git pull origin main

# 3. 依存関係更新
composer install --no-dev --optimize-autoloader

# 4. キャッシュクリア・再生成
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. マイグレーション
php artisan migrate --force

# 6. Queue リスタート
php artisan queue:restart

# 7. メンテナンスモード解除
php artisan up
```

#### ゼロダウンタイムデプロイ

```bash
# Envoy.blade.php（Laravel Envoy）
@servers(['web' => ['deploy@server1', 'deploy@server2']])

@task('deploy', ['on' => 'web', 'parallel' => true])
    cd /var/www/app
    git pull origin main
    composer install --no-dev --optimize-autoloader
    php artisan migrate --force
    php artisan config:cache
    php artisan queue:restart
@endtask
```

#### ロールバック手順

```bash
# 直前のリリースに戻す
cd /var/www/app
git checkout HEAD~1

# キャッシュ再生成
php artisan config:cache
php artisan route:cache

# マイグレーションロールバック（必要な場合）
php artisan migrate:rollback --step=1
```

---

## バックアップ・リカバリ

### バックアップ方針

| 対象 | 方式 | 頻度 | 保持期間 |
|------|------|------|----------|
| データベース | フルバックアップ | 日次 | 30日 |
| データベース | 差分バックアップ | 時間ごと | 7日 |
| ファイルストレージ | 増分バックアップ | 日次 | 90日 |
| 設定ファイル | Git 管理 | 変更時 | 永続 |

### バックアップスクリプト

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/mysql"

# MySQL ダンプ
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# S3 にアップロード
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://backup-bucket/mysql/

# 古いバックアップの削除（30日以上）
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

### リカバリ手順

```bash
# 1. バックアップファイルの取得
aws s3 cp s3://backup-bucket/mysql/db_20240101_000000.sql.gz ./

# 2. 解凍
gunzip db_20240101_000000.sql.gz

# 3. リストア
mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME < db_20240101_000000.sql

# 4. アプリケーションキャッシュクリア
php artisan cache:clear
php artisan config:clear
```

### RTO / RPO

| 指標 | 目標値 | 説明 |
|------|--------|------|
| RTO（目標復旧時間） | 4時間 | 障害発生からサービス復旧まで |
| RPO（目標復旧時点） | 1時間 | 許容されるデータ損失時間 |

---

## 環境別設定

### 環境一覧

| 環境 | 用途 | URL |
|------|------|-----|
| local | 開発 | http://localhost |
| development | 開発検証 | https://dev.example.com |
| staging | リリース前検証 | https://stg.example.com |
| production | 本番 | https://example.com |

### 環境別パラメータ

| パラメータ | local | development | staging | production |
|-----------|-------|-------------|---------|------------|
| APP_DEBUG | true | true | false | false |
| LOG_LEVEL | debug | debug | info | warning |
| CACHE_DRIVER | array | redis | redis | redis |
| QUEUE_CONNECTION | sync | redis | redis | redis |
| SESSION_DRIVER | file | redis | redis | redis |

---

## チェックリスト

### パフォーマンス

- [ ] N+1 クエリが発生していないか
- [ ] 適切なインデックスが設定されているか
- [ ] キャッシュが有効活用されているか
- [ ] 非同期処理すべき処理が Queue に移譲されているか

### 可用性

- [ ] ヘルスチェックエンドポイントが実装されているか
- [ ] 外部サービスへのタイムアウトが設定されているか
- [ ] グレースフルシャットダウンが実装されているか

### 運用性

- [ ] 構造化ログが出力されているか
- [ ] メトリクスが収集されているか
- [ ] アラートが設定されているか
- [ ] デプロイ手順が文書化されているか
- [ ] ロールバック手順が文書化されているか

### バックアップ

- [ ] 定期バックアップが設定されているか
- [ ] リストア手順が検証されているか
- [ ] バックアップの保持期間が適切か

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計標準
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計
