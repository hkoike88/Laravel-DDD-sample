# バックエンド トランザクション設計標準

## 概要

本プロジェクトのバックエンドにおけるトランザクション設計標準を定める。
データの整合性を保証し、競合状態（Race Condition）を防止するための指針と実装パターンを示す。

---

## 基本方針

- **明示的なトランザクション**: 複数の書き込み操作を行う場合は必ずトランザクションを使用
- **最小スコープ**: トランザクションの範囲は必要最小限に
- **UseCase 層での管理**: トランザクション境界は UseCase（Handler）層で制御
- **適切なロック**: 競合状態が発生しうる箇所では適切なロックを使用
- **冪等性の考慮**: リトライ可能な設計を意識

---

## トランザクションが必要なケース

以下のいずれかに該当する場合、トランザクションの使用を検討する。

### 必須

| ケース | 説明 | 例 |
|--------|------|-----|
| 複数テーブルへの書き込み | 複数のテーブルを更新する処理 | 注文作成時に注文テーブルと在庫テーブルを更新 |
| 読み取り後の書き込み | 読み取った値に基づいて書き込む | 在庫数チェック後に減算、管理者数カウント後に降格 |
| 一貫性が必要な複数操作 | 部分的な成功が許容されない | 振込処理（出金と入金は両方成功するか両方失敗） |
| 監査ログとの整合性 | 業務操作と監査ログを同時記録 | 職員更新と監査ログの記録 |

### 検討が必要

| ケース | 説明 | 判断基準 |
|--------|------|---------|
| 単一テーブルの単一行更新 | 1 件のレコード更新 | 他の操作との整合性が必要な場合は使用 |
| 読み取り専用処理 | データ取得のみ | 一貫性のあるスナップショットが必要な場合は使用 |

---

## 競合状態（Race Condition）のパターン

### パターン1: Check-Then-Act（確認後に実行）

最も一般的な競合状態。値を確認してから操作を行う間に、別のリクエストが値を変更する。

```
リクエストA                    リクエストB
    │                              │
    ├─ 管理者数をカウント (1人)      │
    │                              ├─ 管理者数をカウント (1人)
    │                              │
    ├─ 1人以上なので降格OK          ├─ 1人以上なので降格OK
    │                              │
    ├─ 管理者Aを降格                │
    │                              ├─ 管理者Bを降格
    │                              │
    └─ 管理者が0人に！              └─ 管理者が0人に！
```

**対策**: トランザクション + 排他ロック（`FOR UPDATE`）

```php
// Bad: 競合状態が発生する
public function handle(DemoteAdminCommand $command): void
{
    $adminCount = $this->staffRepository->countAdmins();  // ← 読み取り
    if ($adminCount <= 1) {
        throw new LastAdminProtectionException();
    }
    // ↑ この間に別リクエストが管理者を降格する可能性
    $staff->demote();  // ← 書き込み
    $this->staffRepository->save($staff);
}

// Good: トランザクション + 排他ロックで保護
public function handle(DemoteAdminCommand $command): void
{
    return DB::transaction(function () use ($command) {
        // 排他ロック付きでカウント
        $adminCount = $this->staffRepository->countAdminsForUpdate();
        if ($adminCount <= 1) {
            throw new LastAdminProtectionException();
        }
        $staff->demote();
        $this->staffRepository->save($staff);
    });
}
```

### パターン2: Lost Update（更新の消失）

同じレコードを同時に更新し、一方の更新が失われる。

```
リクエストA                    リクエストB
    │                              │
    ├─ 在庫数を取得 (10個)          │
    │                              ├─ 在庫数を取得 (10個)
    │                              │
    ├─ 10 - 3 = 7 を計算           ├─ 10 - 2 = 8 を計算
    │                              │
    ├─ 在庫を7に更新                │
    │                              ├─ 在庫を8に更新 (Aの更新が消失)
    │                              │
    └─ 期待: 5個、実際: 8個         └─
```

**対策**: 楽観的ロック または 悲観的ロック

```php
// 楽観的ロック: updated_at による検証
public function handle(UpdateStockCommand $command): void
{
    return DB::transaction(function () use ($command) {
        $stock = $this->stockRepository->find($command->stockId);

        // クライアントが送信した更新日時と比較
        if ($stock->updatedAt() !== $command->clientUpdatedAt) {
            throw new OptimisticLockException('他のユーザーが更新しました');
        }

        $stock->decrease($command->quantity);
        $this->stockRepository->save($stock);
    });
}

// 悲観的ロック: SELECT ... FOR UPDATE
public function handle(UpdateStockCommand $command): void
{
    return DB::transaction(function () use ($command) {
        // 排他ロックで取得
        $stock = $this->stockRepository->findForUpdate($command->stockId);
        $stock->decrease($command->quantity);
        $this->stockRepository->save($stock);
    });
}
```

### パターン3: 一意制約違反

重複チェック後に挿入する間に、別リクエストが同じ値を挿入する。

```
リクエストA                    リクエストB
    │                              │
    ├─ メールの重複チェック (なし)   │
    │                              ├─ メールの重複チェック (なし)
    │                              │
    ├─ ユーザー作成                 │
    │                              ├─ ユーザー作成 (重複エラー！)
```

**対策**: DB の一意制約 + アプリケーションでの事前チェック（二重防御）

```php
public function handle(CreateUserCommand $command): void
{
    return DB::transaction(function () use ($command) {
        // アプリケーション層でのチェック（ユーザーフレンドリーなエラー）
        if ($this->userRepository->existsByEmail($command->email)) {
            throw new DuplicateEmailException($command->email);
        }

        // 挿入を試行（DB 制約でも保護）
        try {
            $user = User::create(...);
            $this->userRepository->save($user);
        } catch (QueryException $e) {
            if ($this->isDuplicateEntryError($e)) {
                throw new DuplicateEmailException($command->email);
            }
            throw $e;
        }
    });
}
```

---

## トランザクション境界

### レイヤードアーキテクチャでの配置

トランザクション境界は **UseCase（Handler）層** で制御する。

```
┌─────────────────────────────────────────────────────┐
│                  Presentation                        │
│              (Controller / CLI / API)                │
│  ※ トランザクションを開始しない                       │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                    Application                       │
│                  (UseCase / Handler)                 │
│  ★ トランザクション境界をここで制御                   │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                      Domain                          │
│          (Entity / ValueObject / Service)            │
│  ※ トランザクションを意識しない（純粋なビジネスロジック）│
└─────────────────────────────────────────────────────┘
                          ↑
┌─────────────────────────────────────────────────────┐
│                   Infrastructure                     │
│              (Repository 実装 / Eloquent)            │
│  ※ 個別の操作のみ、トランザクション制御はしない        │
└─────────────────────────────────────────────────────┘
```

### 理由

| 層 | 役割 | トランザクション |
|----|------|----------------|
| Presentation | HTTP リクエスト/レスポンス | × 業務ロジックを知らない |
| Application | ユースケースの調整 | ○ 業務の単位を知っている |
| Domain | ビジネスルール | × インフラに依存しない |
| Infrastructure | データアクセス | × 単一操作に専念 |

---

## 実装パターン

### 基本パターン: DB::transaction

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Order\Application\UseCases\Commands\PlaceOrder;

use Illuminate\Support\Facades\DB;

final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly StockRepositoryInterface $stockRepository,
        private readonly OrderEventPublisher $eventPublisher,
    ) {}

    /**
     * 注文確定処理
     *
     * トランザクション内で注文確定と在庫減算を行う。
     */
    public function handle(PlaceOrderCommand $command): PlaceOrderOutput
    {
        return DB::transaction(function () use ($command) {
            // 1. 注文を取得
            $order = $this->orderRepository->find($command->orderId);

            // 2. 在庫を確認・減算（排他ロック付き）
            foreach ($order->lines() as $line) {
                $stock = $this->stockRepository->findForUpdate($line->productId());
                $stock->decrease($line->quantity());
                $this->stockRepository->save($stock);
            }

            // 3. 注文を確定
            $order->place();
            $this->orderRepository->save($order);

            // 4. イベント発行（トランザクション内）
            $this->eventPublisher->publish($order->pullDomainEvents());

            return new PlaceOrderOutput($order->id()->value());
        });
    }
}
```

### ネストしたトランザクション

Laravel の `DB::transaction()` はネストをサポートするが、**内側のトランザクションは独立してコミット/ロールバックできない**（セーブポイントとして機能）。

```php
// 注意: 内側の transaction() は独立したトランザクションではない
DB::transaction(function () {
    // 外側のトランザクション
    $this->orderRepository->save($order);

    DB::transaction(function () {
        // セーブポイントとして機能
        // ここで例外が発生すると、外側も含めてロールバック
        $this->stockRepository->save($stock);
    });
});
```

### トランザクション分離レベル

通常は MySQL のデフォルト（REPEATABLE READ）を使用。特別な要件がある場合のみ変更。

```php
// 分離レベルを指定する場合
DB::transaction(function () {
    // 処理
}, 5, \Illuminate\Database\Connection::TRANSACTION_READ_COMMITTED);
```

| 分離レベル | 特徴 | 用途 |
|-----------|------|------|
| READ UNCOMMITTED | ダーティリード発生 | 使用しない |
| READ COMMITTED | コミット済みのみ読取 | 大量読取（ロック軽減） |
| REPEATABLE READ | 同一トランザクション内で一貫性 | デフォルト（推奨） |
| SERIALIZABLE | 完全な分離 | 厳密な整合性が必要な場合 |

---

## ロックの種類と使い分け

### 楽観的ロック（Optimistic Lock）

競合が少ないことを前提とし、更新時に競合を検出する。

**実装方法**: `updated_at` カラムを使用

```php
// Repository Interface
interface StaffRepositoryInterface
{
    public function find(StaffId $id): Staff;
}

// Handler
public function handle(UpdateStaffCommand $command): void
{
    return DB::transaction(function () use ($command) {
        $staff = $this->staffRepository->find($command->staffId);
        $record = StaffRecord::find($command->staffId);

        // 楽観的ロック検証
        $clientUpdatedAt = new DateTimeImmutable($command->updatedAt);
        if ($record->updated_at->getTimestamp() !== $clientUpdatedAt->getTimestamp()) {
            throw new OptimisticLockException();
        }

        // 更新処理
        $staff->update($command->input);
        $this->staffRepository->save($staff);
    });
}
```

**適用場面**:
- Web フォームからの更新（競合は稀）
- 読み取りが多く、書き込みが少ない
- ユーザーに再試行を促せる

### 悲観的ロック（Pessimistic Lock）

競合を事前に防ぐため、読み取り時にロックを取得する。

**実装方法**: `SELECT ... FOR UPDATE`

```php
// Repository Interface
interface StockRepositoryInterface
{
    /**
     * 排他ロック付きで在庫を取得
     */
    public function findForUpdate(ProductId $id): Stock;
}

// Repository Implementation
public function findForUpdate(ProductId $id): Stock
{
    $record = StockRecord::where('product_id', $id->value())
        ->lockForUpdate()  // SELECT ... FOR UPDATE
        ->firstOrFail();

    return $this->toDomain($record);
}

// 集計クエリでのロック
public function countAdminsForUpdate(): int
{
    return StaffRecord::where('is_admin', true)
        ->lockForUpdate()
        ->count();
}
```

**適用場面**:
- 在庫管理（高頻度の競合）
- 金融処理（データ整合性が最重要）
- Check-Then-Act パターン

### 共有ロック（Shared Lock）

読み取り中に他のトランザクションからの更新を防ぐ。

```php
// SELECT ... FOR SHARE (LOCK IN SHARE MODE)
$record = StaffRecord::where('id', $id)
    ->sharedLock()
    ->first();
```

**適用場面**:
- 一貫性のある読み取りが必要
- 読み取り中に他から更新されたくない
- 複数のトランザクションから同時読み取り可能

### ロックの選択基準

| 条件 | 推奨ロック |
|------|----------|
| 競合が稀、ユーザー操作（フォーム） | 楽観的ロック |
| 競合が頻繁、システム間処理 | 悲観的ロック（FOR UPDATE） |
| 読み取り一貫性のみ必要 | 共有ロック（FOR SHARE） |
| カウント後の判定 | 悲観的ロック（FOR UPDATE） |

---

## アンチパターン

### 1. トランザクション外での複数書き込み

```php
// Bad: トランザクションなしで複数操作
public function handle(TransferCommand $command): void
{
    $from = $this->accountRepository->find($command->fromId);
    $to = $this->accountRepository->find($command->toId);

    $from->withdraw($command->amount);
    $this->accountRepository->save($from);  // ← ここで失敗すると

    $to->deposit($command->amount);
    $this->accountRepository->save($to);    // ← 実行されない（不整合）
}

// Good: トランザクションで保護
public function handle(TransferCommand $command): void
{
    return DB::transaction(function () use ($command) {
        $from = $this->accountRepository->find($command->fromId);
        $to = $this->accountRepository->find($command->toId);

        $from->withdraw($command->amount);
        $to->deposit($command->amount);

        $this->accountRepository->save($from);
        $this->accountRepository->save($to);
    });
}
```

### 2. 長時間トランザクション

```php
// Bad: トランザクション内で外部 API 呼び出し
public function handle(OrderCommand $command): void
{
    return DB::transaction(function () use ($command) {
        $order = $this->orderRepository->find($command->orderId);

        // 外部 API 呼び出し（時間がかかる、ロックが長時間保持される）
        $result = $this->paymentGateway->charge($order->amount());

        $order->markAsPaid($result->transactionId);
        $this->orderRepository->save($order);
    });
}

// Good: 外部 API はトランザクション外で
public function handle(OrderCommand $command): void
{
    $order = $this->orderRepository->find($command->orderId);

    // 外部 API 呼び出し（トランザクション外）
    $result = $this->paymentGateway->charge($order->amount());

    // DB 操作のみトランザクション内
    return DB::transaction(function () use ($order, $result) {
        // 楽観的ロックで競合を検出
        $this->validateNotModified($order);

        $order->markAsPaid($result->transactionId);
        $this->orderRepository->save($order);
    });
}
```

### 3. ロックなしの Check-Then-Act

```php
// Bad: ロックなしでカウント後に判定
public function handle(DemoteCommand $command): void
{
    return DB::transaction(function () use ($command) {
        // トランザクションがあっても、ロックがないと競合する
        $count = $this->staffRepository->countAdmins();  // ← ロックなし
        if ($count <= 1) {
            throw new LastAdminProtectionException();
        }
        // ↑ この間に別トランザクションが管理者を降格する可能性
        $staff->demote();
        $this->staffRepository->save($staff);
    });
}

// Good: 排他ロック付きでカウント
public function handle(DemoteCommand $command): void
{
    return DB::transaction(function () use ($command) {
        // FOR UPDATE でロックを取得
        $count = $this->staffRepository->countAdminsForUpdate();
        if ($count <= 1) {
            throw new LastAdminProtectionException();
        }
        $staff->demote();
        $this->staffRepository->save($staff);
    });
}
```

### 4. Controller でのトランザクション管理

```php
// Bad: Controller でトランザクション管理
class OrderController extends Controller
{
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Controller が業務ロジックを知りすぎている
            $order = Order::create($request->all());
            $this->updateStock($order);
            return response()->json($order);
        });
    }
}

// Good: Handler でトランザクション管理
class OrderController extends Controller
{
    public function store(CreateOrderRequest $request, CreateOrderHandler $handler)
    {
        $output = $handler->handle(new CreateOrderCommand($request->validated()));
        return response()->json($output);
    }
}

class CreateOrderHandler
{
    public function handle(CreateOrderCommand $command): CreateOrderOutput
    {
        return DB::transaction(function () use ($command) {
            // ここでトランザクション管理
        });
    }
}
```

---

## テスト

### トランザクションのテスト

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

final class UpdateStaffHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 正常系: トランザクション内で更新が完了すること
     */
    public function it_updates_staff_within_transaction(): void
    {
        // Arrange
        $staff = StaffFactory::create(['name' => '旧名前']);

        // Act
        $handler = app(UpdateStaffHandler::class);
        $handler->handle(new UpdateStaffCommand(
            staffId: $staff->id,
            input: new UpdateStaffInput(name: '新名前', ...),
        ));

        // Assert
        $this->assertDatabaseHas('staffs', [
            'id' => $staff->id,
            'name' => '新名前',
        ]);
    }

    /**
     * @test
     * 異常系: 例外発生時にロールバックされること
     */
    public function it_rollbacks_on_exception(): void
    {
        // Arrange
        $staff = StaffFactory::create(['name' => '旧名前']);

        // 監査ログで例外を発生させる
        $this->mock(StaffAuditLogger::class)
            ->shouldReceive('logStaffUpdated')
            ->andThrow(new RuntimeException('Audit log failed'));

        // Act & Assert
        $this->expectException(RuntimeException::class);

        try {
            $handler = app(UpdateStaffHandler::class);
            $handler->handle(new UpdateStaffCommand(...));
        } finally {
            // ロールバックされていることを確認
            $this->assertDatabaseHas('staffs', [
                'id' => $staff->id,
                'name' => '旧名前',  // 変更されていない
            ]);
        }
    }
}
```

### 競合状態のテスト

```php
/**
 * @test
 * 最後の管理者を降格しようとするとエラーになること
 */
public function it_prevents_demoting_last_admin(): void
{
    // Arrange: 管理者が1人だけ
    $admin = StaffFactory::create(['is_admin' => true]);

    // Act & Assert
    $this->expectException(LastAdminProtectionException::class);

    $handler = app(UpdateStaffHandler::class);
    $handler->handle(new UpdateStaffCommand(
        staffId: $admin->id,
        operatorId: 'other-operator-id',
        input: new UpdateStaffInput(isAdmin: false, ...),
    ));
}

/**
 * @test
 * 楽観的ロック: 競合が検出されること
 */
public function it_detects_optimistic_lock_conflict(): void
{
    // Arrange
    $staff = StaffFactory::create();
    $originalUpdatedAt = $staff->updated_at->toIso8601String();

    // 別のプロセスで更新（競合状態をシミュレート）
    StaffRecord::where('id', $staff->id)->update(['name' => '別の更新']);

    // Act & Assert
    $this->expectException(OptimisticLockException::class);

    $handler = app(UpdateStaffHandler::class);
    $handler->handle(new UpdateStaffCommand(
        staffId: $staff->id,
        input: new UpdateStaffInput(
            updatedAt: $originalUpdatedAt,  // 古い更新日時
            ...
        ),
    ));
}
```

---

## チェックリスト

### 設計時

- [ ] 複数の書き込み操作がある場合、トランザクションを使用しているか
- [ ] トランザクション境界は UseCase（Handler）層で管理しているか
- [ ] Check-Then-Act パターンがある場合、適切なロックを使用しているか
- [ ] 外部 API 呼び出しはトランザクション外にあるか
- [ ] トランザクションの範囲は必要最小限か

### 実装時

- [ ] `DB::transaction()` を使用しているか
- [ ] 排他ロックが必要な箇所で `lockForUpdate()` を使用しているか
- [ ] 楽観的ロックが必要な箇所で `updated_at` を検証しているか
- [ ] 例外発生時に適切にロールバックされるか
- [ ] デッドロックの可能性を考慮しているか（ロック順序の統一）

### レビュー時

- [ ] Handler に複数のリポジトリ操作がある場合、トランザクションで囲まれているか
- [ ] カウント・存在チェック後の操作にロックがあるか
- [ ] 長時間実行される処理がトランザクション内にないか
- [ ] テストで異常系（ロールバック）が検証されているか

---

## 関連ドキュメント

- [07_DatabaseDesign.md](./07_DatabaseDesign.md) - データベース設計標準
- [06_ErrorHandling.md](./06_ErrorHandling.md) - エラーハンドリング設計標準
- [01_CodingStandards.md](./01_CodingStandards.md) - コーディング規約
- [01_ArchitectureDesign](../../20_architecture/backend/01_ArchitectureDesign/) - アーキテクチャ設計標準

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2026-01-06 | 初版作成 |
