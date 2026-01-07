# バックエンド トランザクション整合性保証設計

## 概要

本プロジェクトにおける分散システム・非同期処理でのトランザクション整合性保証設計を定める。
単一データベースのトランザクションを超えた、サービス間・システム間での整合性を保証するためのパターンと実装指針を示す。

---

## 基本方針

- **結果整合性（Eventual Consistency）**: 分散システムでは即座の整合性より結果整合性を優先
- **冪等性の確保**: すべての操作は再実行可能に設計
- **補償可能性**: 失敗時にはロールバックではなく補償トランザクションで対応
- **可観測性**: 処理状態を追跡・監視可能に
- **障害耐性**: 部分的な障害でもシステム全体が停止しない

---

## パターン一覧

| パターン | 用途 | 複雑度 | 整合性 |
|---------|------|--------|--------|
| Saga パターン | 複数サービス間のトランザクション | 高 | 結果整合性 |
| 2フェーズコミット（2PC） | 分散データベース間の整合性 | 高 | 強整合性 |
| 補償トランザクション | 失敗時のロールバック代替 | 中 | 結果整合性 |
| アウトボックスパターン | DB 更新とイベント発行の整合性 | 中 | 結果整合性 |
| 冪等性設計 | 再実行時の安全性確保 | 低〜中 | - |
| At-least-once 設計 | メッセージ配信の信頼性 | 低〜中 | 結果整合性 |

---

## Saga パターン

### 概要

複数のローカルトランザクションを連携させ、分散トランザクションを実現するパターン。
各ステップが失敗した場合は、補償トランザクションで前のステップを取り消す。

### 種類

| 種類 | 特徴 | 用途 |
|------|------|------|
| コレオグラフィ | イベント駆動、各サービスが自律的に動作 | シンプルなフロー |
| オーケストレーション | 中央の調整役が制御 | 複雑なフロー |

### コレオグラフィ型 Saga

```
┌─────────┐    Event     ┌─────────┐    Event     ┌─────────┐
│ Order   │ ──────────→  │ Payment │ ──────────→  │ Inventory│
│ Service │  OrderCreated│ Service │  PaymentDone │ Service │
└─────────┘              └─────────┘              └─────────┘
     ↑                        │                        │
     │    PaymentFailed       │    StockReserved       │
     └────────────────────────┴────────────────────────┘
```

```php
// イベント定義
final class OrderCreatedEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly int $amount,
        public readonly array $items,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}

final class PaymentCompletedEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}

final class PaymentFailedEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $reason,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}

// Order Service: 注文作成
final class CreateOrderHandler
{
    public function handle(CreateOrderCommand $command): void
    {
        DB::transaction(function () use ($command) {
            $order = Order::create($command);
            $this->orderRepository->save($order);

            // イベント発行（Outbox パターン推奨）
            $this->eventPublisher->publish(new OrderCreatedEvent(
                orderId: $order->id()->value(),
                customerId: $command->customerId,
                amount: $order->totalAmount(),
                items: $order->items(),
                occurredAt: new DateTimeImmutable(),
            ));
        });
    }
}

// Payment Service: 決済処理
final class ProcessPaymentHandler
{
    public function handle(OrderCreatedEvent $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                $payment = $this->paymentGateway->charge(
                    $event->customerId,
                    $event->amount,
                );

                $this->paymentRepository->save($payment);

                $this->eventPublisher->publish(new PaymentCompletedEvent(
                    orderId: $event->orderId,
                    paymentId: $payment->id(),
                    occurredAt: new DateTimeImmutable(),
                ));
            });
        } catch (PaymentException $e) {
            // 補償イベント発行
            $this->eventPublisher->publish(new PaymentFailedEvent(
                orderId: $event->orderId,
                reason: $e->getMessage(),
                occurredAt: new DateTimeImmutable(),
            ));
        }
    }
}

// Order Service: 補償トランザクション（決済失敗時）
final class CancelOrderOnPaymentFailedHandler
{
    public function handle(PaymentFailedEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $order = $this->orderRepository->find(OrderId::from($event->orderId));
            $order->cancel($event->reason);
            $this->orderRepository->save($order);
        });
    }
}
```

### オーケストレーション型 Saga

```
                    ┌──────────────┐
                    │    Saga      │
                    │ Orchestrator │
                    └──────┬───────┘
           ┌───────────────┼───────────────┐
           ↓               ↓               ↓
     ┌─────────┐     ┌─────────┐     ┌─────────┐
     │ Order   │     │ Payment │     │ Inventory│
     │ Service │     │ Service │     │ Service │
     └─────────┘     └─────────┘     └─────────┘
```

```php
// Saga 状態管理
enum OrderSagaState: string
{
    case STARTED = 'started';
    case ORDER_CREATED = 'order_created';
    case PAYMENT_PENDING = 'payment_pending';
    case PAYMENT_COMPLETED = 'payment_completed';
    case PAYMENT_FAILED = 'payment_failed';
    case STOCK_RESERVED = 'stock_reserved';
    case STOCK_FAILED = 'stock_failed';
    case COMPLETED = 'completed';
    case COMPENSATING = 'compensating';
    case COMPENSATED = 'compensated';
}

// Saga Orchestrator
final class OrderSagaOrchestrator
{
    public function __construct(
        private readonly OrderSagaRepository $sagaRepository,
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Saga を開始
     */
    public function start(CreateOrderCommand $command): string
    {
        $sagaId = Ulid::generate();

        $saga = new OrderSaga(
            id: $sagaId,
            state: OrderSagaState::STARTED,
            data: $command->toArray(),
        );

        $this->sagaRepository->save($saga);
        $this->executeStep($saga);

        return $sagaId;
    }

    /**
     * 次のステップを実行
     */
    public function executeStep(OrderSaga $saga): void
    {
        try {
            match ($saga->state) {
                OrderSagaState::STARTED => $this->createOrder($saga),
                OrderSagaState::ORDER_CREATED => $this->processPayment($saga),
                OrderSagaState::PAYMENT_COMPLETED => $this->reserveStock($saga),
                OrderSagaState::STOCK_RESERVED => $this->complete($saga),
                default => null,
            };
        } catch (SagaStepException $e) {
            $this->startCompensation($saga, $e);
        }
    }

    /**
     * 補償処理を開始
     */
    private function startCompensation(OrderSaga $saga, SagaStepException $e): void
    {
        $saga->transitionTo(OrderSagaState::COMPENSATING);
        $saga->setFailureReason($e->getMessage());
        $this->sagaRepository->save($saga);

        // 逆順で補償を実行
        $this->compensate($saga);
    }

    /**
     * 補償トランザクションを実行
     */
    private function compensate(OrderSaga $saga): void
    {
        $completedSteps = $saga->completedSteps();

        foreach (array_reverse($completedSteps) as $step) {
            match ($step) {
                'payment' => $this->paymentService->refund($saga->paymentId),
                'order' => $this->orderService->cancel($saga->orderId),
                default => null,
            };
        }

        $saga->transitionTo(OrderSagaState::COMPENSATED);
        $this->sagaRepository->save($saga);
    }

    // 各ステップの実装...
}

// Saga 永続化モデル
Schema::create('order_sagas', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('state', 30);
    $table->json('data');
    $table->json('completed_steps')->default('[]');
    $table->string('order_id', 26)->nullable();
    $table->string('payment_id', 26)->nullable();
    $table->string('reservation_id', 26)->nullable();
    $table->text('failure_reason')->nullable();
    $table->timestamps();

    $table->index('state');
});
```

### Saga パターンの選択基準

| 条件 | 推奨 |
|------|------|
| サービス数が少ない（2〜3） | コレオグラフィ |
| サービス数が多い（4以上） | オーケストレーション |
| フローが線形 | コレオグラフィ |
| フローに分岐・条件がある | オーケストレーション |
| 可観測性が重要 | オーケストレーション |
| サービスの独立性が重要 | コレオグラフィ |

---

## 2フェーズコミット（2PC）

### 概要

分散データベース間で強整合性を実現するプロトコル。
コーディネーターが全参加者に準備→コミットの2段階で指示を出す。

### フロー

```
Phase 1: Prepare（準備）
┌─────────────┐
│ Coordinator │
└──────┬──────┘
       │ PREPARE
       ├──────────→ Participant A: "Ready"
       └──────────→ Participant B: "Ready"

Phase 2: Commit（確定）
┌─────────────┐
│ Coordinator │
└──────┬──────┘
       │ COMMIT
       ├──────────→ Participant A: "Committed"
       └──────────→ Participant B: "Committed"
```

### 実装例

```php
/**
 * 2PC コーディネーター
 *
 * 注意: 2PC は障害耐性が低いため、
 * 可能な限り Saga パターンを検討すること
 */
final class TwoPhaseCommitCoordinator
{
    /** @var array<TransactionParticipant> */
    private array $participants = [];

    public function addParticipant(TransactionParticipant $participant): void
    {
        $this->participants[] = $participant;
    }

    /**
     * 2PC を実行
     *
     * @throws TwoPhaseCommitException
     */
    public function execute(callable $operation): void
    {
        $transactionId = Ulid::generate();

        try {
            // Phase 1: Prepare
            $this->prepareAll($transactionId);

            // 全員が準備完了したら実行
            $operation();

            // Phase 2: Commit
            $this->commitAll($transactionId);

        } catch (PrepareException $e) {
            // 準備失敗: 全員にアボート
            $this->abortAll($transactionId);
            throw new TwoPhaseCommitException('Prepare phase failed', previous: $e);

        } catch (CommitException $e) {
            // コミット失敗: リカバリが必要（要手動介入）
            $this->logCriticalFailure($transactionId, $e);
            throw new TwoPhaseCommitException('Commit phase failed - manual recovery required', previous: $e);
        }
    }

    private function prepareAll(string $transactionId): void
    {
        foreach ($this->participants as $participant) {
            if (!$participant->prepare($transactionId)) {
                throw new PrepareException("Participant {$participant->name()} not ready");
            }
        }
    }

    private function commitAll(string $transactionId): void
    {
        foreach ($this->participants as $participant) {
            $participant->commit($transactionId);
        }
    }

    private function abortAll(string $transactionId): void
    {
        foreach ($this->participants as $participant) {
            try {
                $participant->abort($transactionId);
            } catch (Throwable $e) {
                // アボート失敗はログに記録して継続
                Log::error("Abort failed for {$participant->name()}", [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

// 参加者インターフェース
interface TransactionParticipant
{
    public function name(): string;
    public function prepare(string $transactionId): bool;
    public function commit(string $transactionId): void;
    public function abort(string $transactionId): void;
}
```

### 2PC の制限事項

| 問題 | 説明 | 対策 |
|------|------|------|
| ブロッキング | 参加者が応答しないと全体が停止 | タイムアウト設定 |
| 単一障害点 | コーディネーター障害で全体停止 | コーディネーターの冗長化 |
| パフォーマンス | ロック時間が長い | 可能なら Saga を使用 |
| リカバリ | コミット途中の障害は手動復旧 | 運用手順の整備 |

**推奨**: 2PC は最後の手段。可能な限り Saga パターンと結果整合性を採用すること。

---

## 補償トランザクション（Compensating Transaction）

### 概要

失敗時にロールバックする代わりに、「逆の操作」を実行して状態を元に戻すパターン。
Saga パターンの基礎となる概念。

### 設計原則

1. **すべての操作に補償操作を定義**: 操作と補償はペアで設計
2. **補償も失敗しうる**: 補償のリトライ機構が必要
3. **冪等性**: 補償操作も冪等に設計
4. **セマンティック等価**: 完全な元戻しでなくてもビジネス的に等価であればよい

### 実装パターン

```php
// 補償可能な操作のインターフェース
interface CompensatableOperation
{
    /**
     * 操作を実行
     */
    public function execute(): OperationResult;

    /**
     * 補償操作を実行
     */
    public function compensate(OperationResult $result): void;

    /**
     * 操作名（ログ用）
     */
    public function name(): string;
}

// 決済操作の例
final class ChargePaymentOperation implements CompensatableOperation
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly string $customerId,
        private readonly int $amount,
    ) {}

    public function execute(): OperationResult
    {
        $charge = $this->gateway->charge($this->customerId, $this->amount);

        return new OperationResult([
            'charge_id' => $charge->id,
            'amount' => $this->amount,
        ]);
    }

    public function compensate(OperationResult $result): void
    {
        // 補償: 返金処理
        $this->gateway->refund(
            chargeId: $result->get('charge_id'),
            amount: $result->get('amount'),
        );
    }

    public function name(): string
    {
        return 'charge_payment';
    }
}

// 在庫予約操作の例
final class ReserveStockOperation implements CompensatableOperation
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly string $productId,
        private readonly int $quantity,
    ) {}

    public function execute(): OperationResult
    {
        $reservation = $this->inventoryService->reserve(
            $this->productId,
            $this->quantity,
        );

        return new OperationResult([
            'reservation_id' => $reservation->id,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ]);
    }

    public function compensate(OperationResult $result): void
    {
        // 補償: 予約キャンセル
        $this->inventoryService->cancelReservation(
            $result->get('reservation_id'),
        );
    }

    public function name(): string
    {
        return 'reserve_stock';
    }
}

// 補償可能な操作の実行器
final class CompensatableOperationExecutor
{
    /** @var array<array{operation: CompensatableOperation, result: OperationResult}> */
    private array $completedOperations = [];

    /**
     * 操作を順次実行し、失敗時は補償を実行
     *
     * @param array<CompensatableOperation> $operations
     */
    public function executeAll(array $operations): void
    {
        try {
            foreach ($operations as $operation) {
                $result = $operation->execute();

                $this->completedOperations[] = [
                    'operation' => $operation,
                    'result' => $result,
                ];

                Log::info("Operation completed: {$operation->name()}");
            }
        } catch (Throwable $e) {
            Log::error("Operation failed, starting compensation", [
                'failed_operation' => $operation->name(),
                'error' => $e->getMessage(),
            ]);

            $this->compensateAll();

            throw new CompensatableOperationException(
                "Operation failed: {$operation->name()}",
                previous: $e,
            );
        }
    }

    /**
     * 完了した操作を逆順で補償
     */
    private function compensateAll(): void
    {
        foreach (array_reverse($this->completedOperations) as $completed) {
            try {
                $completed['operation']->compensate($completed['result']);

                Log::info("Compensation completed: {$completed['operation']->name()}");
            } catch (Throwable $e) {
                // 補償失敗はログに記録して継続
                Log::critical("Compensation failed - manual intervention required", [
                    'operation' => $completed['operation']->name(),
                    'result' => $completed['result']->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

### 補償トランザクションの注意点

| 注意点 | 説明 |
|--------|------|
| 完全な元戻しは不可能な場合がある | 例: 送信済みメールは取り消せない → 訂正メールで対応 |
| 補償にも失敗しうる | リトライ機構と手動復旧手順が必要 |
| 時間差がある | ユーザーに一時的な不整合が見える可能性 |
| 監査ログ | 操作と補償の両方を記録すること |

---

## アウトボックスパターン（Transactional Outbox）

### 概要

データベース更新とイベント発行を同一トランザクションで行い、整合性を保証するパターン。
「DB 更新は成功したがイベント発行に失敗」という状態を防ぐ。

### 問題: 二重書き込み問題

```php
// Bad: 二重書き込み問題
public function handle(CreateOrderCommand $command): void
{
    DB::transaction(function () use ($command) {
        $order = Order::create($command);
        $this->orderRepository->save($order);  // DB 更新
    });

    // ↑ ここで障害が発生すると、DB は更新されているが
    // イベントは発行されない（不整合）
    $this->eventPublisher->publish(new OrderCreatedEvent(...));
}
```

### 解決: アウトボックスパターン

```
┌─────────────────────────────────────────────────┐
│                  Database                        │
│  ┌───────────┐     ┌───────────────────────┐   │
│  │  orders   │     │      outbox           │   │
│  │  table    │     │      table            │   │
│  └───────────┘     └───────────────────────┘   │
│        ↑                    ↑                   │
│        │                    │                   │
│        └────── 同一トランザクション ──────┘        │
└─────────────────────────────────────────────────┘
                              │
                              ↓ (別プロセスで読み取り)
                    ┌─────────────────┐
                    │  Message Relay  │
                    │   (Polling)     │
                    └────────┬────────┘
                             │
                             ↓
                    ┌─────────────────┐
                    │  Message Broker │
                    │  (Queue/Kafka)  │
                    └─────────────────┘
```

### 実装

```php
// Outbox テーブル
Schema::create('outbox', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('aggregate_type', 100);      // 'Order', 'Payment' など
    $table->string('aggregate_id', 26);         // 対象エンティティの ID
    $table->string('event_type', 100);          // 'OrderCreated', 'PaymentCompleted'
    $table->json('payload');                    // イベントデータ
    $table->timestamp('created_at');
    $table->timestamp('processed_at')->nullable();
    $table->unsignedInteger('retry_count')->default(0);
    $table->text('last_error')->nullable();

    $table->index(['processed_at', 'created_at']);
    $table->index('aggregate_type');
});

// Outbox エントリ作成
final class OutboxEntry
{
    public static function fromEvent(object $event, string $aggregateType, string $aggregateId): self
    {
        return new self(
            id: (new Ulid())->toBase32(),
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            eventType: $event::class,
            payload: json_encode($event),
            createdAt: new DateTimeImmutable(),
        );
    }
}

// Outbox を使用した Handler
final class CreateOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OutboxRepositoryInterface $outboxRepository,
    ) {}

    public function handle(CreateOrderCommand $command): CreateOrderOutput
    {
        return DB::transaction(function () use ($command) {
            // 1. 注文を作成・保存
            $order = Order::create($command);
            $this->orderRepository->save($order);

            // 2. Outbox にイベントを記録（同一トランザクション）
            $event = new OrderCreatedEvent(
                orderId: $order->id()->value(),
                customerId: $command->customerId,
                amount: $order->totalAmount(),
                occurredAt: new DateTimeImmutable(),
            );

            $outboxEntry = OutboxEntry::fromEvent(
                event: $event,
                aggregateType: 'Order',
                aggregateId: $order->id()->value(),
            );

            $this->outboxRepository->save($outboxEntry);

            return new CreateOrderOutput($order->id()->value());
        });
    }
}

// Message Relay（別プロセスで実行）
final class OutboxMessageRelay
{
    public function __construct(
        private readonly OutboxRepositoryInterface $outboxRepository,
        private readonly MessageBroker $messageBroker,
    ) {}

    /**
     * 未処理のメッセージを配信
     * スケジューラーで定期実行（例: 毎秒）
     */
    public function relay(): void
    {
        $entries = $this->outboxRepository->findUnprocessed(limit: 100);

        foreach ($entries as $entry) {
            try {
                // メッセージブローカーに送信
                $this->messageBroker->publish(
                    topic: $this->resolveTopic($entry->eventType),
                    message: $entry->payload,
                    headers: [
                        'event_id' => $entry->id,
                        'event_type' => $entry->eventType,
                        'aggregate_type' => $entry->aggregateType,
                        'aggregate_id' => $entry->aggregateId,
                    ],
                );

                // 処理済みマーク
                $entry->markAsProcessed();
                $this->outboxRepository->save($entry);

            } catch (Throwable $e) {
                $entry->incrementRetryCount();
                $entry->setLastError($e->getMessage());
                $this->outboxRepository->save($entry);

                Log::error('Outbox relay failed', [
                    'entry_id' => $entry->id,
                    'event_type' => $entry->eventType,
                    'retry_count' => $entry->retryCount,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function resolveTopic(string $eventType): string
    {
        return match ($eventType) {
            OrderCreatedEvent::class => 'orders.created',
            PaymentCompletedEvent::class => 'payments.completed',
            default => 'events.default',
        };
    }
}

// Laravel スケジューラーでの設定
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->call(function () {
        app(OutboxMessageRelay::class)->relay();
    })->everySecond();
}
```

### Outbox のクリーンアップ

```php
// 処理済みエントリの定期削除
final class OutboxCleanupCommand extends Command
{
    protected $signature = 'outbox:cleanup {--days=7}';

    public function handle(OutboxRepositoryInterface $repository): void
    {
        $threshold = now()->subDays($this->option('days'));

        $deleted = $repository->deleteProcessedBefore($threshold);

        $this->info("Deleted {$deleted} processed outbox entries");
    }
}
```

---

## 冪等性（Idempotency）設計

### 概要

同じ操作を複数回実行しても、結果が変わらないように設計するパターン。
リトライ、At-least-once 配信、重複リクエストに対応するために必須。

### 冪等性キー

```php
// リクエストに冪等性キーを含める
final class CreatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1'],
            'customer_id' => ['required', 'string'],
        ];
    }
}

// 冪等性テーブル
Schema::create('idempotency_keys', function (Blueprint $table) {
    $table->string('key', 64)->primary();
    $table->string('operation', 100);
    $table->json('request_hash');           // リクエスト内容のハッシュ
    $table->json('response')->nullable();   // 保存されたレスポンス
    $table->string('status', 20);           // pending, completed, failed
    $table->timestamp('created_at');
    $table->timestamp('expires_at');

    $table->index('expires_at');
});

// 冪等性ミドルウェア
final class IdempotencyMiddleware
{
    public function __construct(
        private readonly IdempotencyKeyRepository $repository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key')
            ?? $request->input('idempotency_key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $operation = $request->route()->getName();
        $requestHash = $this->hashRequest($request);

        // 既存のキーを確認
        $existing = $this->repository->find($idempotencyKey);

        if ($existing) {
            // リクエスト内容が異なる場合はエラー
            if ($existing->requestHash !== $requestHash) {
                return response()->json([
                    'error' => 'Idempotency key reused with different request',
                ], 422);
            }

            // 処理中の場合は待機
            if ($existing->status === 'pending') {
                return response()->json([
                    'error' => 'Request is being processed',
                ], 409);
            }

            // 完了済みの場合は保存されたレスポンスを返す
            if ($existing->status === 'completed') {
                return response()->json(
                    $existing->response,
                    $existing->responseStatus,
                );
            }
        }

        // 新規キーを登録
        $entry = new IdempotencyKeyEntry(
            key: $idempotencyKey,
            operation: $operation,
            requestHash: $requestHash,
            status: 'pending',
            createdAt: now(),
            expiresAt: now()->addHours(24),
        );
        $this->repository->save($entry);

        try {
            $response = $next($request);

            // レスポンスを保存
            $entry->complete($response->getContent(), $response->getStatusCode());
            $this->repository->save($entry);

            return $response;

        } catch (Throwable $e) {
            $entry->fail($e->getMessage());
            $this->repository->save($entry);
            throw $e;
        }
    }

    private function hashRequest(Request $request): string
    {
        return hash('sha256', json_encode([
            'method' => $request->method(),
            'path' => $request->path(),
            'body' => $request->all(),
        ]));
    }
}
```

### 冪等な操作の設計パターン

```php
// パターン1: UPSERT（存在すれば更新、なければ作成）
public function createOrUpdateUser(CreateUserCommand $command): void
{
    DB::transaction(function () use ($command) {
        UserRecord::updateOrCreate(
            ['external_id' => $command->externalId],  // 一意キー
            [
                'name' => $command->name,
                'email' => $command->email,
                'updated_at' => now(),
            ]
        );
    });
}

// パターン2: 条件付き更新（状態チェック）
public function processPayment(ProcessPaymentCommand $command): void
{
    DB::transaction(function () use ($command) {
        $payment = $this->paymentRepository->findForUpdate($command->paymentId);

        // 既に処理済みなら何もしない（冪等）
        if ($payment->isProcessed()) {
            return;
        }

        $payment->process();
        $this->paymentRepository->save($payment);
    });
}

// パターン3: イベント重複排除
final class EventDeduplicator
{
    public function __construct(
        private readonly ProcessedEventRepository $repository,
    ) {}

    /**
     * イベントが未処理の場合のみ処理を実行
     */
    public function executeOnce(string $eventId, callable $handler): void
    {
        // 既に処理済みかチェック
        if ($this->repository->exists($eventId)) {
            Log::info("Event already processed, skipping", ['event_id' => $eventId]);
            return;
        }

        DB::transaction(function () use ($eventId, $handler) {
            // 処理済みとして記録（先に記録して二重実行を防ぐ）
            $this->repository->markAsProcessed($eventId);

            // 実際の処理を実行
            $handler();
        });
    }
}

// 使用例
$this->eventDeduplicator->executeOnce(
    eventId: $event->eventId,
    handler: fn() => $this->handleOrderCreated($event),
);
```

---

## リトライ＋再実行前提設計（At-least-once）

### 概要

メッセージが少なくとも1回は配信されることを保証する設計。
重複配信の可能性があるため、冪等性とセットで設計する。

### メッセージ配信の保証レベル

| 保証レベル | 説明 | 実装難易度 |
|-----------|------|-----------|
| At-most-once | 最大1回（欠損の可能性あり） | 低 |
| At-least-once | 最低1回（重複の可能性あり） | 中 |
| Exactly-once | 正確に1回 | 高（実質的に不可能） |

**推奨**: At-least-once + 冪等性で Exactly-once 相当を実現

### リトライ戦略

```php
// リトライ設定
final class RetryConfig
{
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly int $initialDelayMs = 100,
        public readonly int $maxDelayMs = 10000,
        public readonly float $multiplier = 2.0,
        public readonly float $jitter = 0.1,
    ) {}

    /**
     * 指数バックオフ + ジッター
     */
    public function getDelayMs(int $attempt): int
    {
        $delay = $this->initialDelayMs * pow($this->multiplier, $attempt - 1);
        $delay = min($delay, $this->maxDelayMs);

        // ジッターを追加（±10%）
        $jitterRange = $delay * $this->jitter;
        $delay += random_int((int) -$jitterRange, (int) $jitterRange);

        return (int) $delay;
    }
}

// リトライ実行器
final class RetryExecutor
{
    public function __construct(
        private readonly RetryConfig $config = new RetryConfig(),
    ) {}

    /**
     * リトライ付きで処理を実行
     *
     * @template T
     * @param callable(): T $operation
     * @param array<class-string<Throwable>> $retryableExceptions
     * @return T
     */
    public function execute(callable $operation, array $retryableExceptions = [Throwable::class]): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->config->maxAttempts; $attempt++) {
            try {
                return $operation();

            } catch (Throwable $e) {
                $lastException = $e;

                if (!$this->shouldRetry($e, $retryableExceptions, $attempt)) {
                    throw $e;
                }

                $delayMs = $this->config->getDelayMs($attempt);

                Log::warning("Operation failed, retrying", [
                    'attempt' => $attempt,
                    'max_attempts' => $this->config->maxAttempts,
                    'delay_ms' => $delayMs,
                    'error' => $e->getMessage(),
                ]);

                usleep($delayMs * 1000);
            }
        }

        throw new RetryExhaustedException(
            "Max retry attempts ({$this->config->maxAttempts}) exceeded",
            previous: $lastException,
        );
    }

    private function shouldRetry(Throwable $e, array $retryableExceptions, int $attempt): bool
    {
        if ($attempt >= $this->config->maxAttempts) {
            return false;
        }

        foreach ($retryableExceptions as $retryableException) {
            if ($e instanceof $retryableException) {
                return true;
            }
        }

        return false;
    }
}
```

### Laravel Queue でのリトライ設定

```php
// Job クラスでのリトライ設定
final class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大試行回数
     */
    public int $tries = 3;

    /**
     * タイムアウト（秒）
     */
    public int $timeout = 30;

    /**
     * 指数バックオフ（秒）
     */
    public array $backoff = [1, 5, 10];

    /**
     * 一意性（重複防止）
     */
    public function uniqueId(): string
    {
        return $this->paymentId;
    }

    /**
     * 一意性の有効期間
     */
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $paymentId,
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        // 冪等な処理
        $paymentService->processIfNotProcessed($this->paymentId);
    }

    /**
     * 失敗時の処理
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Payment processing failed permanently', [
            'payment_id' => $this->paymentId,
            'error' => $exception->getMessage(),
        ]);

        // 手動対応が必要な場合は通知
        Notification::send(
            new AdminNotification("Payment {$this->paymentId} failed: {$exception->getMessage()}")
        );
    }
}

// config/queue.php での設定
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,      // ジョブがタイムアウトしたとみなす秒数
        'block_for' => 5,
        'after_commit' => true,   // トランザクションコミット後に dispatch
    ],
],
```

### Dead Letter Queue（DLQ）

```php
// 失敗したジョブを DLQ に移動
final class MoveToDeadLetterQueue
{
    public function handle(JobFailed $event): void
    {
        $payload = [
            'job_id' => $event->job->uuid(),
            'job_class' => get_class($event->job),
            'payload' => $event->job->payload(),
            'exception' => $event->exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
        ];

        // DLQ に記録
        DeadLetterRecord::create([
            'id' => (new Ulid())->toBase32(),
            'queue' => $event->job->getQueue(),
            'payload' => json_encode($payload),
            'exception' => $event->exception->getMessage(),
            'failed_at' => now(),
        ]);
    }
}

// DLQ からの再処理コマンド
final class RetryDeadLetterCommand extends Command
{
    protected $signature = 'dlq:retry {id}';

    public function handle(): void
    {
        $record = DeadLetterRecord::findOrFail($this->argument('id'));

        $payload = json_decode($record->payload, true);
        $jobClass = $payload['job_class'];

        // ジョブを再投入
        dispatch(new $jobClass(...$payload['constructor_args']));

        $record->update(['retried_at' => now()]);

        $this->info("Job {$record->id} has been retried");
    }
}
```

---

## チェックリスト

### 設計時

- [ ] 分散トランザクションが本当に必要か（単一 DB で解決できないか）
- [ ] 強整合性が必要か、結果整合性で許容できるか
- [ ] Saga パターンを使用する場合、各ステップの補償操作を定義したか
- [ ] アウトボックスパターンを使用してイベント発行の整合性を確保したか
- [ ] すべての操作に冪等性を持たせたか
- [ ] リトライ戦略（回数、バックオフ）を定義したか

### 実装時

- [ ] 冪等性キーを適切に使用しているか
- [ ] Outbox テーブルへの書き込みが業務トランザクションと同一か
- [ ] Message Relay が定期実行されているか
- [ ] 補償トランザクションが冪等に設計されているか
- [ ] Dead Letter Queue が設定されているか
- [ ] 手動リカバリ手順が文書化されているか

### 運用時

- [ ] Saga の状態を監視・可視化できるか
- [ ] Outbox の未処理メッセージを監視しているか
- [ ] DLQ のメッセージを定期的に確認しているか
- [ ] 補償失敗時のアラートが設定されているか

---

## 関連ドキュメント

- [09_TransactionDesign.md](./09_TransactionDesign.md) - トランザクション設計標準
- [13_ExternalIntegration.md](./13_ExternalIntegration.md) - 外部連携設計標準
- [14_BatchProcessing.md](./14_BatchProcessing.md) - バッチ処理設計標準
- [06_ErrorHandling.md](./06_ErrorHandling.md) - エラーハンドリング設計標準

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2026-01-06 | 初版作成 |
