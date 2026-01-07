# バックエンド イベント駆動設計標準

## 概要

本プロジェクトにおけるイベント駆動アーキテクチャ（Event-Driven Architecture / EDA）の設計標準を定める。
サービス間の疎結合化、非同期処理によるスケーラビリティと耐障害性の確保、イベント欠損・重複・順序問題による業務不整合の防止を目的とする。

---

## 基本方針

- **At-least-once 前提**: イベント配信は At-least-once を基本とし、Exactly-once を前提としない
- **冪等性の必須化**: すべてのコンシューマは冪等に設計する
- **事実の通知**: イベントは「すでに起きた事実」を表現し、命令（Command）ではない
- **順序非依存**: イベントの順序に依存する設計は禁止
- **Outbox パターン**: DB 更新とイベント発行は Transactional Outbox で整合性を保証

---

## イベントの定義

### イベントとは

イベントとは「**すでに起きた事実**」を表現するものである。

| 特性 | 説明 |
|------|------|
| 事実の記録 | 過去に発生した出来事を表す |
| 命令ではない | Command（〜せよ）ではなく Event（〜した）|
| 不変性 | 再実行や再送されても意味が変わらない |

### 命名規則

イベント名は **過去形** を用い、ドメインイベントとして表現する。

```php
// 良い例：過去形で事実を表現
class OrderPlaced implements DomainEvent { }
class PaymentCompleted implements DomainEvent { }
class BalanceDeducted implements DomainEvent { }

// 悪い例：命令形やあいまいな表現
class PlaceOrder { }      // 命令形は NG
class PaymentProcess { }  // あいまいな表現は NG
```

---

## イベント設計ルール

### 最小限の事実のみを持つ

```php
// 良い例：必要最小限の情報
class OrderPlaced implements DomainEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}

// 悪い例：参照用データを詰め込みすぎ
class OrderPlaced implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly array $orderItems,        // 不要：必要なら取得する
        public readonly array $customerDetails,   // 不要：必要なら取得する
        public readonly array $shippingAddress,   // 不要：必要なら取得する
    ) {}
}
```

### スキーマ変更は後方互換を守る

| 操作 | 可否 | 説明 |
|------|------|------|
| フィールド追加 | ○ | 新しいフィールドの追加は許可 |
| フィールド削除 | × | 既存のコンシューマが壊れる |
| フィールドの意味変更 | × | 暗黙の契約違反 |
| フィールド名変更 | × | 既存のコンシューマが壊れる |

### イベント ID と一意性

すべてのイベントは一意な `event_id` を持ち、再送時も同一 `event_id` を使用する。

```php
class DomainEventBase
{
    public function __construct(
        public readonly string $eventId,      // ULID で一意性を保証
        public readonly DateTimeImmutable $occurredAt,
    ) {
        // event_id は生成時に決定し、再送時も変更しない
    }
}
```

---

## イベント配信保証と Outbox

### 基本方針

DB 更新とイベント発行は **Transactional Outbox** を用いて保証する。

```
┌─────────────────────────────────────────────────────┐
│                  同一トランザクション                  │
│  ┌─────────────────┐    ┌─────────────────────┐    │
│  │   業務テーブル    │    │   Outbox テーブル    │    │
│  │   UPDATE/INSERT │    │   INSERT (イベント)  │    │
│  └─────────────────┘    └─────────────────────┘    │
└─────────────────────────────────────────────────────┘
                              │
                              ▼ 非同期
                    ┌─────────────────┐
                    │  イベント送信処理  │
                    │  （別プロセス）    │
                    └─────────────────┘
```

### Outbox 必須条件

| 条件 | 説明 |
|------|------|
| 同一トランザクション | DB 更新と Outbox 書き込みは同一トランザクション内で実行 |
| 再送可能な状態管理 | Outbox は `pending` / `sent` / `failed` などの状態を持つ |
| 非同期送信 | イベント送信は非同期処理として実行する |

### Outbox テーブル設計例

```php
// マイグレーション
Schema::create('outbox_events', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('event_type');
    $table->json('payload');
    $table->string('status')->default('pending'); // pending, sent, failed
    $table->unsignedInteger('retry_count')->default(0);
    $table->timestamp('created_at');
    $table->timestamp('sent_at')->nullable();
    $table->index(['status', 'created_at']);
});
```

---

## コンシューマ設計標準

### 冪等性の必須化

同一イベントを複数回処理しても結果が変わらないこと。`event_id` による処理済み判定を行う。

```php
class OrderPlacedHandler
{
    public function handle(OrderPlaced $event): void
    {
        // 処理済みチェック
        if ($this->isAlreadyProcessed($event->eventId)) {
            Log::info('Event already processed', ['event_id' => $event->eventId]);
            return;
        }

        DB::transaction(function () use ($event) {
            // 業務処理
            $this->processOrder($event);

            // 処理済みとして記録
            $this->markAsProcessed($event->eventId);
        });
    }

    private function isAlreadyProcessed(string $eventId): bool
    {
        return ProcessedEvent::where('event_id', $eventId)->exists();
    }

    private function markAsProcessed(string $eventId): void
    {
        ProcessedEvent::create([
            'event_id' => $eventId,
            'processed_at' => now(),
        ]);
    }
}
```

### 再試行設計

| エラー種別 | 対応 |
|-----------|------|
| 一時エラー（タイムアウト、一時的な接続障害） | 再試行（Exponential Backoff） |
| 恒久エラー（バリデーションエラー、不正データ） | DLQ（Dead Letter Queue）へ送信 |

```php
class EventConsumerJob implements ShouldQueue
{
    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300]; // 秒

    public function handle(): void
    {
        try {
            $this->processEvent();
        } catch (TemporaryException $e) {
            // 再試行可能な例外は再スロー
            throw $e;
        } catch (PermanentException $e) {
            // 恒久エラーは DLQ へ
            $this->moveToDeadLetterQueue($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        // 最終的に失敗した場合の処理
        Log::critical('Event processing failed permanently', [
            'event_id' => $this->event->eventId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

### 順序依存を作らない

イベントの順序に依存する設計は禁止。必要な場合は状態を明示的に確認する。

```php
// 悪い例：順序に依存
class ShipmentStartedHandler
{
    public function handle(ShipmentStarted $event): void
    {
        // PaymentCompleted が先に届いている前提 → NG
        $this->ship($event->orderId);
    }
}

// 良い例：状態を明示的に確認
class ShipmentStartedHandler
{
    public function handle(ShipmentStarted $event): void
    {
        $order = $this->orderRepository->find($event->orderId);

        // 状態を確認してから処理
        if (!$order->isPaid()) {
            Log::info('Order not yet paid, skipping shipment');
            return;
        }

        $this->ship($order);
    }
}
```

---

## Saga との関係

### Saga パターンとの連携

複数イベントを跨ぐ業務フローは Saga として設計する。

```
┌────────────┐     OrderPlaced      ┌────────────┐
│   Order    │ ─────────────────▶  │  Payment   │
│  Service   │                      │  Service   │
└────────────┘                      └────────────┘
      ▲                                   │
      │                          PaymentCompleted
      │                                   │
      │       ShipmentStarted            ▼
      │  ◀─────────────────────   ┌────────────┐
      │                           │  Shipment  │
      └───────────────────────────│  Service   │
                                  └────────────┘
```

### 補償イベントの設計

失敗時は補償イベントを発行する。

```php
// 正常系イベント
class PaymentCompleted implements DomainEvent { }

// 補償イベント
class PaymentRefunded implements DomainEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $paymentId,
        public readonly string $orderId,
        public readonly string $reason, // 補償理由
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
```

---

## Laravel での実装パターン

### イベントクラスの定義

```php
namespace Packages\Domain\Order\Domain\Events;

use DateTimeImmutable;

final class OrderPlaced
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly int $totalAmount,
        public readonly DateTimeImmutable $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'order_id' => $this->orderId,
            'customer_id' => $this->customerId,
            'total_amount' => $this->totalAmount,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
```

### Outbox 経由でのイベント発行

```php
namespace Packages\Domain\Order\Application\UseCases\Commands\PlaceOrder;

use Illuminate\Support\Facades\DB;
use Packages\Domain\Order\Domain\Events\OrderPlaced;
use Packages\Shared\Infrastructure\Outbox\OutboxRepository;

final class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OutboxRepository $outboxRepository,
    ) {}

    public function handle(PlaceOrderCommand $command): void
    {
        DB::transaction(function () use ($command) {
            // 1. 業務処理
            $order = Order::create(
                $command->customerId,
                $command->items,
            );
            $this->orderRepository->save($order);

            // 2. Outbox にイベントを記録（同一トランザクション）
            $event = new OrderPlaced(
                eventId: (string) new Ulid(),
                orderId: $order->id()->value(),
                customerId: $command->customerId,
                totalAmount: $order->totalAmount(),
                occurredAt: new DateTimeImmutable(),
            );
            $this->outboxRepository->store($event);
        });
    }
}
```

### Outbox リレーヤー（イベント送信処理）

```php
namespace Packages\Shared\Infrastructure\Outbox;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class OutboxRelayerCommand extends Command
{
    protected $signature = 'outbox:relay';
    protected $description = 'Relay events from outbox to message broker';

    public function handle(OutboxRepository $outbox, EventPublisher $publisher): void
    {
        $events = $outbox->getPendingEvents(limit: 100);

        foreach ($events as $event) {
            try {
                DB::transaction(function () use ($event, $outbox, $publisher) {
                    // イベント送信
                    $publisher->publish($event);

                    // 送信済みとしてマーク
                    $outbox->markAsSent($event->id);
                });
            } catch (Throwable $e) {
                $outbox->incrementRetryCount($event->id);
                Log::error('Failed to relay event', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

---

## アンチパターン

### 禁止事項一覧

| アンチパターン | 問題点 |
|---------------|--------|
| Exactly-once 前提の実装 | ネットワーク障害時に整合性が破綻する |
| イベントでの業務ロジック分岐 | イベントは事実の通知であり、分岐条件を持つべきではない |
| コンシューマ側でのトランザクション跨ぎ処理 | 複数サービスを跨ぐトランザクションは Saga で設計する |
| 再送不能なイベント設計 | event_id がないイベントは冪等性を保証できない |

### 具体的な NG 例

```php
// NG: Exactly-once 前提
class PaymentHandler
{
    public function handle(PaymentRequested $event): void
    {
        // 重複チェックなしで決済実行 → 二重課金の危険
        $this->paymentGateway->charge($event->amount);
    }
}

// NG: イベントでの業務ロジック分岐
class OrderPlaced
{
    public bool $shouldNotifyVip;      // イベントに分岐条件を持たせるのは NG
    public bool $requiresApproval;     // コンシューマが判断すべき
}

// NG: 再送不能なイベント（event_id なし）
class OrderPlaced
{
    public function __construct(
        public readonly string $orderId,  // event_id がない → 冪等性保証不可
    ) {}
}
```

---

## チェックリスト

### 設計時

- [ ] イベントは「事実」を表しているか（過去形の命名）
- [ ] 重複・再送を前提とした設計か
- [ ] At-least-once 配信を前提としているか
- [ ] イベントの順序に依存していないか
- [ ] スキーマの後方互換性を維持しているか

### 実装時

- [ ] Transactional Outbox を利用しているか
- [ ] コンシューマは冪等か（event_id による重複チェック）
- [ ] 再試行戦略が定義されているか
- [ ] DLQ（Dead Letter Queue）が設定されているか
- [ ] すべてのイベントに一意な event_id があるか

### レビュー時

- [ ] DB 更新と Outbox 書き込みが同一トランザクションか
- [ ] コンシューマが状態を明示的に確認しているか（順序非依存）
- [ ] 補償イベントが必要な場合、定義されているか
- [ ] エラーハンドリング（一時エラー/恒久エラー）が適切か

---

## 関連ドキュメント

- [09_TransactionDesign.md](./09_TransactionDesign.md) - トランザクション設計標準
- [10_TransactionConsistencyDesign.md](./10_TransactionConsistencyDesign.md) - トランザクション整合性保証設計
- [11_TransactionConsistencyChecklist.md](./11_TransactionConsistencyChecklist.md) - トランザクション整合性チェックリスト
- [13_ExternalIntegration.md](./13_ExternalIntegration.md) - 外部連携設計標準

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2026-01-06 | 初版作成 |
