# バックエンド トランザクション整合性チェックリスト

## 概要

本資料は、**At-least-once 前提の再試行設計**を共通基盤とし、
システム特性・業務特性に応じて**最低限どの設計要素を採用すべきか**を判断するための
設計標準チェックリストである。

新機能開発・設計レビュー時に本チェックリストを使用し、必要な設計要素の漏れを防ぐ。

---

## 設計原則

> **再試行（At-least-once）は整合性を保証しない。**
> **整合性は明示的な設計要素（冪等性・Outbox・Saga）によってのみ担保される。**

- ネットワーク障害・タイムアウト・プロセスクラッシュは必ず発生する
- 「失敗しない設計」ではなく **「失敗しても壊れない設計」** を採用する
- Exactly-once を前提とした設計は行わない

---

## 基本方針

- 本システムは **At-least-once（再試行前提）** を基本とする
- 再試行により処理が **重複実行される可能性を常に考慮** する
- 重複実行が問題となる処理は、必ず本資料に基づき設計要素を選定する

---

## 判断フロー

以下の質問に **YES** が付いたものが、その機能・処理で**必須となる設計要素**である。

> **注意**: A / B / C は排他的ではない。
> 複数に YES が付いた場合は、その**すべてを採用すること**。

| 判断質問 | YES の場合に必須となる設計 |
|----------|--------------------------|
| 同じ処理が複数回実行されると致命的か？ | 冪等性（Idempotency） |
| DB 更新とイベント送信が分離しているか？ | Transactional Outbox |
| 複数ステップ・複数サービスに跨るか？ | Saga（補償トランザクション） |

---

## A. 冪等性チェック（二重実行が致命的な処理）

### 該当する代表例

- 決済実行
- チャージ確定
- 残高加算・減算
- 請求・課金確定

### チェック項目

- [ ] 再試行により**二重課金・二重反映**が発生しうる
- [ ] 外部 API 呼び出し後、成功/失敗が曖昧になる可能性がある
- [ ] Webhook や API リクエストが**重複して届く**可能性がある

### YES の場合の最小セット

> **At-least-once + 冪等性（Idempotency）**

#### 必須要件

| 要件 | 説明 |
|------|------|
| 冪等性キー | 一意な `idempotency_key` を付与する |
| 結果の再利用 | 同一キーの処理結果を再利用する |
| DB 制約 | DB レベルで一意制約を設ける |

#### 実装例

```php
// 冪等性キーによる重複チェック
public function processPayment(ProcessPaymentCommand $command): PaymentResult
{
    return DB::transaction(function () use ($command) {
        // 既に処理済みかチェック
        $existing = $this->idempotencyRepository->find($command->idempotencyKey);
        if ($existing && $existing->isCompleted()) {
            return $existing->result();  // 保存された結果を返す
        }

        // 処理を実行
        $result = $this->paymentGateway->charge($command);

        // 結果を保存
        $this->idempotencyRepository->save(
            $command->idempotencyKey,
            $result,
        );

        return $result;
    });
}
```

#### 例外的に省略できる条件

以下を**すべて満たす場合のみ**省略可能：

- [ ] 同一 DB トランザクション内で完結している
- [ ] 外部 I/O（API・メッセージ送信等）が存在しない
- [ ] 再試行が発生しないことを技術的に保証できる

---

## B. Outbox チェック（DB 更新とイベント送信の整合性）

### 該当する代表例

- 注文確定イベント
- 決済完了通知
- 在庫引当完了通知

### チェック項目

- [ ] DB 更新後にイベントを送信している
- [ ] イベント送信失敗時の再送が難しい
- [ ] 「DB は更新されたが通知されていない」状態が問題になる

### YES の場合の最小セット

> **At-least-once + Transactional Outbox**

#### 必須要件

| 要件 | 説明 |
|------|------|
| 同一トランザクション | DB 更新と Outbox 書き込みを同一トランザクションで行う |
| 状態管理 | Outbox は再送可能な状態管理を持つ |
| 非同期送信 | イベント送信は非同期で行う |

#### 実装例

```php
public function createOrder(CreateOrderCommand $command): void
{
    DB::transaction(function () use ($command) {
        // 1. 注文を保存
        $order = Order::create($command);
        $this->orderRepository->save($order);

        // 2. Outbox にイベントを記録（同一トランザクション）
        $this->outboxRepository->save(
            OutboxEntry::fromEvent(
                new OrderCreatedEvent($order->id()),
                aggregateType: 'Order',
                aggregateId: $order->id()->value(),
            )
        );
    });
    // イベント送信は Message Relay が非同期で実行
}
```

#### 例外的に省略できる条件

以下を**すべて満たす場合のみ**省略可能：

- [ ] DB 更新とイベント送信が同一トランザクションで保証されている
- [ ] イベント未送信が業務上問題にならない

---

## C. Saga チェック（複数ステップの整合性）

### 該当する代表例

- 注文 → 在庫 → 決済 → 配送
- チャージ → 残高反映 → 利用可能化

### チェック項目

- [ ] 処理が複数のステップに分かれている
- [ ] 外部サービス・別コンポーネントを跨いでいる
- [ ] 途中失敗時に「元に戻す」業務要件が存在する

### YES の場合の最小セット

> **At-least-once + Saga（補償トランザクション）**

#### 必須要件

| 要件 | 説明 |
|------|------|
| 独立した再実行 | 各ステップは独立して再実行可能であること |
| 補償処理の定義 | 失敗時の補償処理を明示的に定義する |
| 状態遷移管理 | 状態遷移（PENDING / COMPLETED / FAILED 等）を管理する |

#### 実装例

```php
// Saga 状態管理
enum OrderSagaState: string
{
    case STARTED = 'started';
    case PAYMENT_COMPLETED = 'payment_completed';
    case STOCK_RESERVED = 'stock_reserved';
    case COMPLETED = 'completed';
    case COMPENSATING = 'compensating';
    case COMPENSATED = 'compensated';
}

// 補償処理の定義
final class OrderSaga
{
    public function compensate(): void
    {
        foreach (array_reverse($this->completedSteps) as $step) {
            match ($step) {
                'payment' => $this->paymentService->refund($this->paymentId),
                'stock' => $this->inventoryService->releaseReservation($this->reservationId),
                default => null,
            };
        }
    }
}
```

---

## 設計要素の役割整理

| 要素 | 役割 | 対処する問題 |
|------|------|-------------|
| At-least-once | 成功率を上げる（耐障害性） | 一時的な障害 |
| 冪等性 | 重複実行でも壊れない | 二重処理 |
| Transactional Outbox | DB とイベントの一貫性 | 部分的な成功 |
| Saga | ビジネス整合性の維持 | 分散トランザクション |

---

## 設計レビューでの利用方法

### レビュー手順

1. 新機能・仕様変更時に本チェックリストを必ず実施する
2. YES が付いた項目の設計要素が未実装の場合は差し戻す
3. 例外的に不要と判断した場合は、その理由を設計書に明記する

### レビュー指摘テンプレート

#### 冪等性が不足している場合

```
本処理は At-least-once 前提であり、
二重実行が致命的（A に該当）であるにも関わらず
冪等性設計が確認できません。

本設計標準 A 項に基づき、
idempotency_key を用いた冪等化を実施してください。

参照: 13_TransactionConsistencyChecklist.md - A. 冪等性チェック
```

#### Outbox が不足している場合

```
本処理は DB 更新後にイベントを送信しており、
Transactional Outbox パターン（B に該当）が必要ですが、
実装が確認できません。

本設計標準 B 項に基づき、
Outbox パターンを実装してください。

参照: 13_TransactionConsistencyChecklist.md - B. Outbox チェック
```

#### Saga が不足している場合

```
本処理は複数サービスを跨ぐフローであり、
Saga パターン（C に該当）が必要ですが、
補償トランザクションの設計が確認できません。

本設計標準 C 項に基づき、
各ステップの補償処理を定義してください。

参照: 13_TransactionConsistencyChecklist.md - C. Saga チェック
```

---

## 関連ドキュメント

- [09_TransactionDesign.md](./09_TransactionDesign.md) - トランザクション設計標準
- [10_TransactionConsistencyDesign.md](./10_TransactionConsistencyDesign.md) - トランザクション整合性保証設計
- [07_ErrorHandling.md](./07_ErrorHandling.md) - エラーハンドリング設計標準
- [13_ExternalIntegration.md](./13_ExternalIntegration.md) - 外部連携設計標準

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2026-01-06 | 初版作成 |
