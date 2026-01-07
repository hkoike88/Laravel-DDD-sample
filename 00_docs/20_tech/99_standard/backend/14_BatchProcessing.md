# バックエンド バッチ処理設計標準

## 概要

本プロジェクトのバックエンドにおけるバッチ処理（スケジュールタスク、キュージョブ、大量データ処理）の設計標準を定める。
冪等性、エラーハンドリング、監視を適切に実装し、信頼性の高いバッチ処理を実現する。

---

## 基本方針

- **冪等性**: 同じ処理を複数回実行しても結果が変わらない設計
- **再開可能性**: 途中で失敗しても、失敗箇所から再開できる設計
- **監視可能性**: 処理状況をログ・メトリクスで可視化
- **リソース効率**: メモリ・CPU を適切に使用し、システムに過負荷をかけない
- **障害耐性**: 一部の失敗が全体に波及しない設計

---

## バッチ処理の種類

| 種類 | 説明 | Laravel 機能 |
|------|------|-------------|
| スケジュールタスク | 定期実行されるタスク | Task Scheduling |
| キュージョブ | 非同期で実行されるジョブ | Queue |
| Artisan コマンド | CLI から実行するコマンド | Console Command |
| 一括処理 | 大量データの一括処理 | Chunk / Cursor |

---

## スケジュールタスク設計

### 基本構成

```php
// app/Console/Kernel.php
<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

final class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 毎日深夜3時: 延滞通知
        $schedule->command('batch:notify-overdue')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // 毎時: 予約期限切れ処理
        $schedule->command('batch:expire-reservations')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // 毎日深夜1時: 統計集計
        $schedule->command('batch:aggregate-statistics')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->emailOutputOnFailure('admin@example.com');

        // 毎分: キューヘルスチェック
        $schedule->command('queue:monitor redis:default --max=1000')
            ->everyMinute();
    }
}
```

### スケジュール設定オプション

| オプション | 説明 | 推奨 |
|-----------|------|------|
| `withoutOverlapping()` | 前回の実行が完了していない場合はスキップ | 必須 |
| `onOneServer()` | 複数サーバー環境で1台のみ実行 | 必須 |
| `runInBackground()` | バックグラウンドで実行 | 長時間タスク |
| `appendOutputTo()` | 出力をファイルに追記 | 推奨 |
| `emailOutputOnFailure()` | 失敗時にメール通知 | 重要タスク |

### スケジュール頻度

```php
// 頻度の設定例
$schedule->command('...')->everyMinute();        // 毎分
$schedule->command('...')->everyFiveMinutes();   // 5分ごと
$schedule->command('...')->everyTenMinutes();    // 10分ごと
$schedule->command('...')->everyFifteenMinutes();// 15分ごと
$schedule->command('...')->everyThirtyMinutes(); // 30分ごと
$schedule->command('...')->hourly();             // 毎時
$schedule->command('...')->hourlyAt(15);         // 毎時15分
$schedule->command('...')->daily();              // 毎日深夜0時
$schedule->command('...')->dailyAt('03:00');     // 毎日3時
$schedule->command('...')->weekly();             // 毎週日曜0時
$schedule->command('...')->weeklyOn(1, '8:00');  // 毎週月曜8時
$schedule->command('...')->monthly();            // 毎月1日0時
$schedule->command('...')->monthlyOn(15, '09:00');// 毎月15日9時

// 条件付き実行
$schedule->command('...')
    ->daily()
    ->when(fn () => app()->environment('production'));

// 時間帯指定
$schedule->command('...')
    ->hourly()
    ->between('08:00', '20:00');  // 8時〜20時の間のみ

// 除外時間帯
$schedule->command('...')
    ->hourly()
    ->unlessBetween('23:00', '05:00');  // 23時〜5時以外
```

---

## キュージョブ設計

### 基本構造

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大試行回数
     */
    public int $tries = 3;

    /**
     * タイムアウト（秒）
     */
    public int $timeout = 60;

    /**
     * リトライ間隔（秒）
     * 配列で指数バックオフを実現
     */
    public array $backoff = [10, 60, 300];  // 10秒, 1分, 5分

    /**
     * ジョブを一意にするキー
     */
    public function uniqueId(): string
    {
        return 'notification-' . $this->userId;
    }

    /**
     * 一意性の有効期間（秒）
     */
    public int $uniqueFor = 3600;

    public function __construct(
        private readonly string $userId,
        private readonly string $message,
    ) {}

    /**
     * ジョブの実行
     */
    public function handle(NotificationService $service): void
    {
        Log::info('SendNotificationJob started', [
            'user_id' => $this->userId,
            'attempt' => $this->attempts(),
        ]);

        $service->send($this->userId, $this->message);

        Log::info('SendNotificationJob completed', [
            'user_id' => $this->userId,
        ]);
    }

    /**
     * ジョブ失敗時の処理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // 失敗通知を送信
        // Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new JobFailedNotification($this, $exception));
    }

    /**
     * リトライすべきかを判定
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // 特定の例外はリトライしない
        if ($exception instanceof InvalidUserException) {
            return false;
        }

        return true;
    }

    /**
     * ジョブが実行されるべきキューを指定
     */
    public function viaQueue(): string
    {
        return 'notifications';
    }
}
```

### ジョブのディスパッチ

```php
// 基本的なディスパッチ
SendNotificationJob::dispatch($userId, $message);

// 遅延実行
SendNotificationJob::dispatch($userId, $message)
    ->delay(now()->addMinutes(5));

// 特定のキューに送信
SendNotificationJob::dispatch($userId, $message)
    ->onQueue('high-priority');

// 特定の接続を使用
SendNotificationJob::dispatch($userId, $message)
    ->onConnection('redis');

// チェーン実行（順番に実行）
Bus::chain([
    new ProcessOrderJob($orderId),
    new SendConfirmationEmailJob($orderId),
    new UpdateInventoryJob($orderId),
])->dispatch();

// バッチ実行（並列実行）
Bus::batch([
    new SendEmailJob($user1),
    new SendEmailJob($user2),
    new SendEmailJob($user3),
])
->then(fn (Batch $batch) => Log::info('Batch completed'))
->catch(fn (Batch $batch, \Throwable $e) => Log::error('Batch failed'))
->finally(fn (Batch $batch) => Log::info('Batch finished'))
->dispatch();

// 条件付きディスパッチ
SendNotificationJob::dispatchIf($shouldSend, $userId, $message);
SendNotificationJob::dispatchUnless($shouldSkip, $userId, $message);
```

### キュー設定

```php
// config/queue.php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => true,  // トランザクション完了後にディスパッチ
        ],
    ],

    // 失敗したジョブの保存先
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
```

### キュー別の用途

```php
// 優先度別のキュー定義
final class QueueNames
{
    // 高優先度: 即座に処理が必要
    public const HIGH = 'high-priority';

    // 通常: 一般的な非同期処理
    public const DEFAULT = 'default';

    // 低優先度: 時間に余裕がある処理
    public const LOW = 'low-priority';

    // 通知: メール・プッシュ通知
    public const NOTIFICATIONS = 'notifications';

    // レポート: レポート生成
    public const REPORTS = 'reports';

    // 外部API: 外部サービス連携
    public const EXTERNAL = 'external-api';
}

// ワーカー起動（優先度順に処理）
// php artisan queue:work --queue=high-priority,default,low-priority
```

### ワーカー設定

```bash
# 開発環境
php artisan queue:work

# 本番環境
php artisan queue:work redis \
    --queue=high-priority,default,low-priority \
    --sleep=3 \
    --tries=3 \
    --max-jobs=1000 \
    --max-time=3600 \
    --memory=128
```

| オプション | 説明 | 推奨値 |
|-----------|------|--------|
| `--sleep` | ジョブがない時の待機時間（秒） | 3 |
| `--tries` | デフォルトの最大試行回数 | 3 |
| `--max-jobs` | 処理するジョブの最大数 | 1000 |
| `--max-time` | ワーカーの最大実行時間（秒） | 3600 |
| `--memory` | メモリ上限（MB） | 128 |

### Supervisor 設定

```ini
; /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/worker.log
stopwaitsecs=3600

[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/artisan queue:work redis --queue=high-priority --sleep=1 --tries=3 --max-jobs=500
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/worker-high.log
stopwaitsecs=3600
```

---

## Artisan コマンド設計

### 基本構造

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class NotifyOverdueCommand extends Command
{
    /**
     * コマンドのシグネチャ
     */
    protected $signature = 'batch:notify-overdue
                            {--dry-run : 実際には処理せずログ出力のみ}
                            {--limit=1000 : 処理件数の上限}';

    /**
     * コマンドの説明
     */
    protected $description = '延滞している貸出の通知を送信する';

    /**
     * コマンドの実行
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('延滞通知バッチを開始します');
        $this->info("Dry Run: " . ($isDryRun ? 'Yes' : 'No'));
        $this->info("Limit: {$limit}");

        Log::channel('batch')->info('NotifyOverdueCommand started', [
            'dry_run' => $isDryRun,
            'limit' => $limit,
        ]);

        $startTime = microtime(true);
        $processedCount = 0;
        $errorCount = 0;

        try {
            $overdueLoans = $this->getOverdueLoans($limit);
            $total = $overdueLoans->count();

            $this->info("対象件数: {$total}");
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($overdueLoans as $loan) {
                try {
                    if (!$isDryRun) {
                        $this->processLoan($loan);
                    }
                    $processedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::channel('batch')->error('Loan processing failed', [
                        'loan_id' => $loan->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $duration = round(microtime(true) - $startTime, 2);

            $this->info("処理完了: {$processedCount}件成功, {$errorCount}件失敗");
            $this->info("処理時間: {$duration}秒");

            Log::channel('batch')->info('NotifyOverdueCommand completed', [
                'processed' => $processedCount,
                'errors' => $errorCount,
                'duration_seconds' => $duration,
            ]);

            return $errorCount > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("バッチ処理中にエラーが発生しました: {$e->getMessage()}");

            Log::channel('batch')->error('NotifyOverdueCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function getOverdueLoans(int $limit): \Illuminate\Support\Collection
    {
        return LoanModel::query()
            ->where('status', 'active')
            ->where('due_date', '<', now())
            ->whereNull('notified_at')
            ->limit($limit)
            ->cursor();
    }

    private function processLoan(LoanModel $loan): void
    {
        // 通知処理
        SendOverdueNotificationJob::dispatch($loan->id);

        // 通知済みフラグを更新
        $loan->update(['notified_at' => now()]);
    }
}
```

### コマンドオプションの設計

```php
protected $signature = 'batch:process-data
    {date? : 処理対象日（YYYY-MM-DD）、省略時は前日}
    {--dry-run : 実際には処理せずログ出力のみ}
    {--force : 確認なしで実行}
    {--limit=1000 : 処理件数の上限}
    {--chunk=100 : チャンクサイズ}
    {--queue= : 使用するキュー名}';

public function handle(): int
{
    $date = $this->argument('date') ?? now()->subDay()->format('Y-m-d');
    $isDryRun = $this->option('dry-run');
    $force = $this->option('force');
    $limit = (int) $this->option('limit');
    $chunkSize = (int) $this->option('chunk');

    // 確認プロンプト（--force がない場合）
    if (!$force && !$this->confirm("日付 {$date} のデータを処理しますか？")) {
        $this->info('処理をキャンセルしました');
        return self::SUCCESS;
    }

    // 処理...
}
```

---

## 大量データ処理

### チャンク処理

大量のレコードを一定件数ずつ処理する。

```php
// 基本的なチャンク処理
LoanModel::query()
    ->where('status', 'active')
    ->chunk(1000, function ($loans) {
        foreach ($loans as $loan) {
            $this->processLoan($loan);
        }
    });

// ID ベースのチャンク（途中再開可能）
LoanModel::query()
    ->where('status', 'active')
    ->chunkById(1000, function ($loans) {
        foreach ($loans as $loan) {
            $this->processLoan($loan);
        }
    });

// 進捗表示付き
$total = LoanModel::where('status', 'active')->count();
$bar = $this->output->createProgressBar($total);

LoanModel::query()
    ->where('status', 'active')
    ->chunkById(1000, function ($loans) use ($bar) {
        foreach ($loans as $loan) {
            $this->processLoan($loan);
            $bar->advance();
        }
    });

$bar->finish();
```

### カーソル処理

メモリ効率が最も良い。1件ずつ取得して処理。

```php
// カーソル処理（メモリ効率最高）
foreach (LoanModel::where('status', 'active')->cursor() as $loan) {
    $this->processLoan($loan);
}

// Lazy Collection として使用
LoanModel::where('status', 'active')
    ->cursor()
    ->filter(fn ($loan) => $loan->isOverdue())
    ->each(fn ($loan) => $this->notifyOverdue($loan));
```

### 一括更新

大量レコードの一括更新。

```php
// 一括更新（メモリ効率が良い）
$affected = LoanModel::query()
    ->where('due_date', '<', now()->subDays(30))
    ->where('status', 'overdue')
    ->update(['status' => 'archived']);

$this->info("{$affected}件をアーカイブしました");

// 分割して更新（ロック時間を短縮）
$totalUpdated = 0;
do {
    $updated = LoanModel::query()
        ->where('due_date', '<', now()->subDays(30))
        ->where('status', 'overdue')
        ->limit(1000)
        ->update(['status' => 'archived']);

    $totalUpdated += $updated;

    // 他の処理に CPU を譲る
    usleep(100000);  // 0.1秒

} while ($updated > 0);

$this->info("{$totalUpdated}件をアーカイブしました");
```

### 一括挿入

```php
// 一括挿入
$records = [];
foreach ($data as $item) {
    $records[] = [
        'id' => (new Ulid())->toBase32(),
        'name' => $item['name'],
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

// チャンクで挿入（大量データ時）
collect($records)->chunk(1000)->each(function ($chunk) {
    RecordModel::insert($chunk->toArray());
});

// upsert（存在すれば更新、なければ挿入）
RecordModel::upsert(
    $records,
    ['id'],  // 一意キー
    ['name', 'updated_at']  // 更新するカラム
);
```

---

## 冪等性の確保

### 冪等性とは

同じ処理を複数回実行しても、結果が1回実行した場合と同じになる性質。

### 冪等性を確保するパターン

**1. 処理済みフラグ**

```php
final class ProcessOrderBatchCommand extends Command
{
    public function handle(): int
    {
        OrderModel::query()
            ->where('status', 'pending')
            ->whereNull('processed_at')  // 未処理のみ対象
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        // 処理を実行
                        $this->processOrder($order);

                        // 処理済みフラグを更新
                        $order->update(['processed_at' => now()]);
                    });
                }
            });

        return self::SUCCESS;
    }
}
```

**2. 一意キーによる重複防止**

```php
final class ImportDataCommand extends Command
{
    public function handle(): int
    {
        foreach ($this->getDataToImport() as $data) {
            // external_id が一意キー
            RecordModel::updateOrCreate(
                ['external_id' => $data['id']],
                [
                    'name' => $data['name'],
                    'value' => $data['value'],
                    'imported_at' => now(),
                ]
            );
        }

        return self::SUCCESS;
    }
}
```

**3. 処理結果の記録**

```php
final class BatchProcessingRecord extends Model
{
    protected $table = 'batch_processing_records';

    protected $fillable = [
        'batch_id',
        'target_id',
        'target_type',
        'status',
        'processed_at',
        'error_message',
    ];
}

final class ProcessWithRecordCommand extends Command
{
    public function handle(): int
    {
        $batchId = (new Ulid())->toBase32();

        LoanModel::query()
            ->where('status', 'overdue')
            ->chunkById(100, function ($loans) use ($batchId) {
                foreach ($loans as $loan) {
                    // 既に処理済みかチェック
                    $exists = BatchProcessingRecord::where('target_id', $loan->id)
                        ->where('target_type', 'loan')
                        ->where('status', 'success')
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    try {
                        $this->processLoan($loan);

                        BatchProcessingRecord::create([
                            'batch_id' => $batchId,
                            'target_id' => $loan->id,
                            'target_type' => 'loan',
                            'status' => 'success',
                            'processed_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        BatchProcessingRecord::create([
                            'batch_id' => $batchId,
                            'target_id' => $loan->id,
                            'target_type' => 'loan',
                            'status' => 'failed',
                            'processed_at' => now(),
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
```

**4. ジョブの一意性**

```php
final class SendReminderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $userId,
        private readonly string $type,
    ) {}

    /**
     * 一意キー
     */
    public function uniqueId(): string
    {
        return "{$this->userId}-{$this->type}";
    }

    /**
     * 一意性の有効期間（秒）
     */
    public int $uniqueFor = 3600;

    public function handle(): void
    {
        // 処理
    }
}
```

---

## エラーハンドリング

### コマンドでのエラーハンドリング

```php
public function handle(): int
{
    $errors = [];
    $processed = 0;

    try {
        foreach ($this->getTargets() as $target) {
            try {
                $this->process($target);
                $processed++;
            } catch (\Exception $e) {
                // 個別エラーは記録して続行
                $errors[] = [
                    'target_id' => $target->id,
                    'error' => $e->getMessage(),
                ];

                Log::channel('batch')->error('Processing failed', [
                    'target_id' => $target->id,
                    'error' => $e->getMessage(),
                ]);

                // エラー数が閾値を超えたら中断
                if (count($errors) >= 100) {
                    throw new TooManyErrorsException('エラーが多すぎるため中断します');
                }
            }
        }

        // 結果をサマリー出力
        $this->info("処理完了: {$processed}件成功");

        if (count($errors) > 0) {
            $this->warn(count($errors) . "件のエラーが発生しました");
            $this->table(['Target ID', 'Error'], $errors);
            return self::FAILURE;
        }

        return self::SUCCESS;

    } catch (TooManyErrorsException $e) {
        $this->error($e->getMessage());
        Log::channel('batch')->critical('Batch aborted', [
            'processed' => $processed,
            'errors' => count($errors),
        ]);
        return self::FAILURE;
    }
}
```

### ジョブでのエラーハンドリング

```php
final class ProcessDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [60, 300, 900];  // 1分, 5分, 15分

    public function handle(): void
    {
        // 処理
    }

    /**
     * ジョブ失敗時
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('batch')->error('Job failed permanently', [
            'job' => static::class,
            'data_id' => $this->dataId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // 管理者に通知
        Notification::route('slack', config('services.slack.webhook'))
            ->notify(new JobFailedNotification($this, $exception));
    }

    /**
     * リトライすべきか判定
     */
    public function retryUntil(): \DateTime
    {
        // 24時間後まではリトライ
        return now()->addHours(24);
    }

    /**
     * ミドルウェア
     */
    public function middleware(): array
    {
        return [
            // レート制限
            new RateLimited('external-api'),

            // 同時実行制限
            (new WithoutOverlapping($this->dataId))
                ->dontRelease()
                ->expireAfter(300),
        ];
    }
}
```

### 失敗したジョブの再実行

```bash
# 失敗したジョブの一覧
php artisan queue:failed

# 特定のジョブを再実行
php artisan queue:retry {id}

# 全ての失敗ジョブを再実行
php artisan queue:retry all

# 失敗ジョブの削除
php artisan queue:forget {id}
php artisan queue:flush  # 全削除
```

---

## 監視・アラート

### ログ設計

```php
// config/logging.php
'channels' => [
    'batch' => [
        'driver' => 'daily',
        'path' => storage_path('logs/batch.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

### ログ出力項目

**バッチ開始時:**

```php
Log::channel('batch')->info('Batch started', [
    'command' => 'batch:notify-overdue',
    'options' => [
        'dry_run' => false,
        'limit' => 1000,
    ],
    'started_at' => now()->toIso8601String(),
]);
```

**バッチ完了時:**

```php
Log::channel('batch')->info('Batch completed', [
    'command' => 'batch:notify-overdue',
    'processed' => 500,
    'errors' => 3,
    'skipped' => 10,
    'duration_seconds' => 45.2,
    'memory_peak_mb' => 64,
]);
```

**エラー発生時:**

```php
Log::channel('batch')->error('Batch processing error', [
    'command' => 'batch:notify-overdue',
    'target_id' => $loan->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

### メトリクス

| メトリクス | 説明 | アラート閾値 |
|-----------|------|-------------|
| batch_execution_time | バッチ実行時間 | > 期待時間の2倍 |
| batch_processed_count | 処理件数 | - |
| batch_error_count | エラー件数 | > 10/実行 |
| batch_error_rate | エラー率 | > 5% |
| queue_size | キュー滞留数 | > 10000 |
| queue_wait_time | キュー待機時間 | > 5分 |
| failed_jobs_count | 失敗ジョブ数 | > 0 |

### ヘルスチェック

```php
// app/Console/Commands/QueueHealthCheckCommand.php
final class QueueHealthCheckCommand extends Command
{
    protected $signature = 'queue:health-check';
    protected $description = 'キューのヘルスチェック';

    public function handle(): int
    {
        $queues = ['high-priority', 'default', 'low-priority'];
        $alerts = [];

        foreach ($queues as $queue) {
            $size = Queue::size($queue);

            if ($size > 1000) {
                $alerts[] = "Queue '{$queue}' has {$size} pending jobs";
            }
        }

        // 失敗ジョブ数
        $failedCount = DB::table('failed_jobs')->count();
        if ($failedCount > 0) {
            $alerts[] = "{$failedCount} failed jobs exist";
        }

        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $this->warn($alert);
                Log::channel('batch')->warning($alert);
            }

            // Slack 通知
            Notification::route('slack', config('services.slack.webhook'))
                ->notify(new QueueAlertNotification($alerts));

            return self::FAILURE;
        }

        $this->info('Queue health check passed');
        return self::SUCCESS;
    }
}
```

---

## 実行ログテーブル

### テーブル設計

```php
// マイグレーション
Schema::create('batch_execution_logs', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('command', 100);
    $table->json('options')->nullable();
    $table->string('status', 20);  // running, completed, failed
    $table->timestamp('started_at');
    $table->timestamp('completed_at')->nullable();
    $table->integer('processed_count')->default(0);
    $table->integer('error_count')->default(0);
    $table->integer('skipped_count')->default(0);
    $table->decimal('duration_seconds', 10, 2)->nullable();
    $table->integer('memory_peak_mb')->nullable();
    $table->text('error_message')->nullable();
    $table->json('summary')->nullable();

    $table->index('command');
    $table->index('status');
    $table->index('started_at');
});
```

### 実行ログの記録

```php
trait LogsBatchExecution
{
    protected ?BatchExecutionLog $executionLog = null;

    protected function startBatchLog(array $options = []): void
    {
        $this->executionLog = BatchExecutionLog::create([
            'id' => (new Ulid())->toBase32(),
            'command' => $this->getName(),
            'options' => $options,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    protected function completeBatchLog(int $processed, int $errors = 0, int $skipped = 0): void
    {
        if ($this->executionLog) {
            $this->executionLog->update([
                'status' => $errors > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'processed_count' => $processed,
                'error_count' => $errors,
                'skipped_count' => $skipped,
                'duration_seconds' => now()->diffInSeconds($this->executionLog->started_at),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024),
            ]);
        }
    }

    protected function failBatchLog(string $errorMessage): void
    {
        if ($this->executionLog) {
            $this->executionLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($this->executionLog->started_at),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024),
                'error_message' => $errorMessage,
            ]);
        }
    }
}

// 使用例
final class NotifyOverdueCommand extends Command
{
    use LogsBatchExecution;

    public function handle(): int
    {
        $this->startBatchLog([
            'dry_run' => $this->option('dry-run'),
            'limit' => $this->option('limit'),
        ]);

        try {
            // 処理...
            $this->completeBatchLog($processed, $errors);
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->failBatchLog($e->getMessage());
            return self::FAILURE;
        }
    }
}
```

---

## テスト

### コマンドのテスト

```php
public function test_延滞通知バッチが正常に実行される(): void
{
    // 延滞している貸出を作成
    $loan = LoanModel::factory()->overdue()->create();

    // コマンドを実行
    $this->artisan('batch:notify-overdue')
        ->assertSuccessful()
        ->expectsOutput('延滞通知バッチを開始します');

    // 通知ジョブがディスパッチされたことを確認
    Queue::assertPushed(SendOverdueNotificationJob::class, function ($job) use ($loan) {
        return $job->loanId === $loan->id;
    });

    // 通知済みフラグが更新されたことを確認
    $this->assertNotNull($loan->fresh()->notified_at);
}

public function test_ドライランでは実際に処理しない(): void
{
    $loan = LoanModel::factory()->overdue()->create();

    $this->artisan('batch:notify-overdue', ['--dry-run' => true])
        ->assertSuccessful();

    Queue::assertNothingPushed();
    $this->assertNull($loan->fresh()->notified_at);
}
```

### ジョブのテスト

```php
public function test_通知ジョブが正常に実行される(): void
{
    $user = UserModel::factory()->create();

    $job = new SendNotificationJob($user->id, 'テストメッセージ');
    $job->handle(app(NotificationService::class));

    // 通知が送信されたことを確認
    Notification::assertSentTo($user, OverdueNotification::class);
}

public function test_失敗時にリトライされる(): void
{
    $this->mock(NotificationService::class, function ($mock) {
        $mock->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('送信エラー'));
    });

    $job = new SendNotificationJob('user-1', 'テスト');

    $this->expectException(\Exception::class);
    $job->handle(app(NotificationService::class));

    // リトライ設定を確認
    $this->assertEquals(3, $job->tries);
}
```

---

## チェックリスト

### スケジュールタスク

- [ ] `withoutOverlapping()` が設定されているか
- [ ] `onOneServer()` が設定されているか（複数サーバー環境）
- [ ] ログ出力が実装されているか
- [ ] 失敗時の通知が設定されているか
- [ ] 実行時間帯が適切か

### キュージョブ

- [ ] `tries` / `timeout` が設定されているか
- [ ] `failed()` メソッドが実装されているか
- [ ] リトライ戦略が適切か
- [ ] 一意性が必要な場合に `ShouldBeUnique` を使用しているか
- [ ] ログ出力が実装されているか

### 大量データ処理

- [ ] チャンク処理またはカーソル処理を使用しているか
- [ ] 進捗表示があるか
- [ ] メモリ使用量を考慮しているか
- [ ] 途中再開が可能か

### 冪等性

- [ ] 同じ処理を複数回実行しても問題ないか
- [ ] 処理済みの判定ロジックがあるか
- [ ] 一意キーによる重複防止があるか

### エラーハンドリング

- [ ] 個別エラーで全体が止まらないか
- [ ] エラー件数の閾値があるか
- [ ] エラーログが出力されているか
- [ ] 管理者への通知があるか

### 監視

- [ ] 実行開始/終了のログがあるか
- [ ] 処理件数/エラー件数がログに記録されているか
- [ ] 処理時間がログに記録されているか
- [ ] キュー滞留数の監視があるか

---

## 関連ドキュメント

- [03_Non-FunctionalRequirements.md](./03_Non-FunctionalRequirements.md) - 非機能要件
- [04_LoggingDesign.md](./04_LoggingDesign.md) - ログ設計標準
- [07_ErrorHandling.md](./07_ErrorHandling.md) - エラーハンドリング設計
- [09_TransactionDesign.md](./09_TransactionDesign.md) - トランザクション設計標準
- [11_TransactionConsistencyChecklist.md](./11_TransactionConsistencyChecklist.md) - トランザクション整合性チェックリスト
- [12_EventDrivenDesign.md](./12_EventDrivenDesign.md) - イベント駆動設計標準

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-25 | 初版作成 |
