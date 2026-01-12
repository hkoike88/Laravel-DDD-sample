# バックエンド コーディング規約

## 概要

本プロジェクトの PHP / Laravel コーディング規約を定める。
DDD アーキテクチャに基づく実装パターンも含む。

---

## 基本方針

- PSR-12 に準拠
- Laravel の規約に従う（ただし DDD レイヤーの制約を優先）
- 静的解析ツール（PHPStan / Larastan）でレベル 6 以上をパス
- PHP 8.2+ の機能を積極的に活用
- **すべてのPHPファイルに厳密モード（`declare(strict_types=1);`）を付与する**

---

## フォーマット・静的解析

### 使用ツール

| ツール | 用途 | 設定ファイル |
|--------|------|-------------|
| Laravel Pint | コードフォーマット | `pint.json` |
| PHPStan / Larastan | 静的解析 | `phpstan.neon` |
| PHP CS Fixer | 追加フォーマット（任意） | `.php-cs-fixer.php` |

### 実行コマンド

```bash
# フォーマット
./vendor/bin/pint

# 静的解析
./vendor/bin/phpstan analyse
```

### CI での自動チェック

```yaml
# .github/workflows/ci.yml
- name: Run Pint
  run: ./vendor/bin/pint --test

- name: Run PHPStan
  run: ./vendor/bin/phpstan analyse --no-progress
```

---

## 命名規約

### クラス名

| 種類 | 規約 | 例 |
|------|------|-----|
| Entity | PascalCase | `Order`, `Customer` |
| ValueObject | PascalCase | `OrderId`, `Money`, `Email` |
| Enum | PascalCase | `BookFormat`, `PaymentMethod` |
| Repository Interface | `{Name}RepositoryInterface` | `OrderRepositoryInterface` |
| Repository 実装 | `Eloquent{Name}Repository` | `EloquentOrderRepository` |
| Eloquent Model | `{Name}Record` | `OrderRecord` |
| UseCase Command | `{Action}{Entity}Command` | `PlaceOrderCommand` |
| UseCase Handler | `{Action}{Entity}Handler` | `PlaceOrderHandler` |
| DTO | `{Name}DTO` | `OrderDTO` |
| Controller | `{Name}Controller` | `OrderController` |
| FormRequest | `{Action}{Entity}Request` | `PlaceOrderRequest` |
| Resource | `{Name}Resource` | `OrderResource` |
| Exception | `{Description}Exception` | `OrderNotFoundException` |
| ServiceProvider | `{Domain}ServiceProvider` | `OrderServiceProvider` |

### メソッド名

| 種類 | 規約 | 例 |
|------|------|-----|
| 取得（単一） | `find`, `get` | `find(OrderId $id)` |
| 取得（複数） | `findAll`, `findBy{Condition}` | `findByCustomer(CustomerId $id)` |
| 保存 | `save` | `save(Order $order)` |
| 削除 | `delete` | `delete(Order $order)` |
| 状態変更 | 動詞 | `place()`, `cancel()`, `confirm()` |
| 判定 | `is`, `can`, `has` | `isPlaced()`, `canCancel()` |
| ファクトリ | `from`, `create` | `OrderId::from(1)` |

### 変数名

```php
// ✓ Good: キャメルケース、意味のある名前
$orderId = OrderId::from($request->order_id);
$customerName = $customer->name();

// ✗ Bad: 省略形、意味不明
$oid = OrderId::from($request->order_id);
$n = $customer->name();
```

---

## 型宣言

### 厳密モード（strict_types）の使用

**必須**: すべてのPHPファイルの先頭に `declare(strict_types=1);` を記述する。

```php
<?php

declare(strict_types=1);

namespace App\Models;

// ...
```

**理由**:
- 型の厳密なチェックにより、予期しない型変換を防止
- PHPStan との相性が良く、型推論が正確になる
- 型安全性の向上により、バグの早期発見が可能

**注意事項**:
- `<?php` タグの直後に記述する（空行を挟む）
- namespace 宣言の前に記述する
- すべてのPHPファイルに適用する（テストファイルを含む）

### 必須

- すべてのメソッド引数に型宣言
- すべてのメソッド戻り値に型宣言
- プロパティ型宣言

```php
// ✓ Good
<?php

declare(strict_types=1);

final class Order
{
    public function __construct(
        private OrderId $id,
        private CustomerId $customerId,
        private OrderStatus $status,
    ) {}

    public function place(): void
    {
        // ...
    }

    public function id(): OrderId
    {
        return $this->id;
    }
}

// ✗ Bad: 型宣言なし
class Order
{
    private $id;

    public function place()
    {
        // ...
    }
}
```

### Union 型の使用

```php
// 許可: null との Union
public function find(OrderId $id): ?Order

// 許可: 明確な型の Union
public function parse(string|int $value): OrderId

// 非推奨: 広すぎる Union
public function process(mixed $data): mixed  // ✗
```

---

## クラス設計

### final の使用

継承を前提としないクラスには `final` を付ける。

```php
// ✓ Domain Model は final
final class Order { }
final class OrderId { }
final class Money { }

// ✓ UseCase は final
final class PlaceOrderHandler { }

// ✗ 継承される可能性があるクラス
class BaseController { }  // final なし
```

### readonly の使用

不変オブジェクトには `readonly` を使用。

```php
<?php

declare(strict_types=1);

// ✓ DTO は readonly class
final readonly class OrderDTO
{
    public function __construct(
        public int $id,
        public string $status,
        public int $amount,
    ) {}
}

// ✓ Command / Query は readonly class
final readonly class PlaceOrderCommand
{
    public function __construct(
        public int $orderId,
    ) {}
}
```

### コンストラクタプロモーション

コンストラクタでのプロパティ宣言を推奨。

```php
// ✓ Good: コンストラクタプロモーション
<?php

declare(strict_types=1);

final class Order
{
    public function __construct(
        private OrderId $id,
        private CustomerId $customerId,
        private OrderStatus $status,
    ) {}
}

// ✗ Bad: 冗長な書き方
final class Order
{
    private OrderId $id;
    private CustomerId $customerId;
    private OrderStatus $status;

    public function __construct(
        OrderId $id,
        CustomerId $customerId,
        OrderStatus $status
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->status = $status;
    }
}
```

---

## Domain 層

### Entity

```php
<?php

declare(strict_types=1);

final class Order
{
    /** @var list<object> */
    private array $domainEvents = [];

    public function __construct(
        private OrderId $id,
        private CustomerId $customerId,
        private OrderStatus $status,
        private Money $amount,
    ) {}

    // 状態変更メソッド: ビジネスルールを実装
    public function place(): void
    {
        if (!$this->status->canPlace()) {
            throw new DomainException('確定できない状態です');
        }

        $this->status = OrderStatus::placed();
        $this->domainEvents[] = new OrderPlacedEvent($this->id);
    }

    // Getter: 値を返すのみ
    public function id(): OrderId
    {
        return $this->id;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    // Setter は作らない（状態変更は専用メソッドで）
}
```

**ルール:**
- `final class` を使用
- Setter を作らない
- 状態変更はビジネスロジックを持つメソッドで
- Laravel / Eloquent に依存しない

### ValueObject

```php
<?php

declare(strict_types=1);

final class Money
{
    public function __construct(
        private int $amount,
        private string $currency = 'JPY',
    ) {
        // 自己検証
        if ($amount < 0) {
            throw new InvalidArgumentException('金額は0以上');
        }
    }

    // ファクトリメソッド
    public static function fromInt(int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    // 不変: 変更メソッドは新しいインスタンスを返す
    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new DomainException('通貨が異なります');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    // 等価性
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    // プリミティブへの変換
    public function toInt(): int
    {
        return $this->amount;
    }
}
```

**ルール:**
- `final class` を使用
- コンストラクタで自己検証
- 不変（immutable）: 変更は新インスタンスを返す
- `equals()` メソッドで等価性を判定

> **Note:** 単純な区分値（カテゴリ、フォーマット等）には PHP 8.1+ の `enum` を使用可能。
> 詳細は [02_実装パターン.md](./01_ArchitectureDesign/02_実装パターン.md) の「Enum（列挙型）と ValueObject の使い分け」を参照。

### Repository Interface

```php
<?php

declare(strict_types=1);

interface OrderRepositoryInterface
{
    /**
     * 注文を取得する
     *
     * @throws OrderNotFoundException 注文が存在しない場合
     */
    public function find(OrderId $id): Order;

    /**
     * 顧客の注文一覧を取得する
     *
     * @return list<Order>
     */
    public function findByCustomer(CustomerId $customerId): array;

    /**
     * 注文を保存する
     */
    public function save(Order $order): void;
}
```

**ルール:**
- Domain 層に配置
- 戻り値は Domain Model（Eloquent ではない）
- 配列は `list<Order>` で返す（Collection ではない）
- PHPDoc で例外を明記

---

## Application 層

### UseCase (Command)

```php
<?php

declare(strict_types=1);

final readonly class PlaceOrderCommand
{
    public function __construct(
        public int $orderId,
    ) {}
}

final class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function handle(PlaceOrderCommand $command): void
    {
        $order = $this->orderRepository->find(
            OrderId::from($command->orderId)
        );

        $order->place();

        $this->orderRepository->save($order);
    }
}
```

**ルール:**
- Command は `readonly class`
- Handler は `final class`
- 1 Handler = 1 責務
- Eloquent を直接操作しない

### DTO

```php
<?php

declare(strict_types=1);

final readonly class OrderDTO
{
    public function __construct(
        public int $id,
        public int $customerId,
        public string $status,
        public int $amount,
    ) {}

    public static function fromDomain(Order $order): self
    {
        return new self(
            id: $order->id()->value(),
            customerId: $order->customerId()->value(),
            status: $order->status()->value(),
            amount: $order->amount()->toInt(),
        );
    }
}
```

**ルール:**
- `readonly class` を使用
- `public` プロパティで宣言
- `fromDomain()` で Domain Model から変換

### Repository 実装

```php
<?php

declare(strict_types=1);

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function find(OrderId $id): Order
    {
        $record = OrderRecord::find($id->value());

        if ($record === null) {
            throw new OrderNotFoundException($id);
        }

        return $this->toDomain($record);
    }

    public function save(Order $order): void
    {
        $record = OrderRecord::findOrNew($order->id()->value());
        $record->customer_id = $order->customerId()->value();
        $record->status = $order->status()->value();
        $record->amount = $order->amount()->toInt();
        $record->save();
    }

    private function toDomain(OrderRecord $record): Order
    {
        return new Order(
            OrderId::from($record->id),
            CustomerId::from($record->customer_id),
            OrderStatus::from($record->status),
            Money::fromInt($record->amount),
        );
    }
}
```

**ルール:**
- `final class` を使用
- `toDomain()` で Eloquent → Domain 変換
- `save()` で Domain → Eloquent 変換

---

## Infrastructure 層

### Eloquent Model

```php
<?php

declare(strict_types=1);

/**
 * @property int $id
 * @property int $customer_id
 * @property string $status
 * @property int $amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class OrderRecord extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'status',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    // リレーションのみ定義
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerRecord::class, 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLineRecord::class, 'order_id');
    }
}
```

**ルール:**
- 命名は `{Name}Record`
- PHPDoc で `@property` を記述
- ビジネスロジックを書かない
- リレーションと casts のみ定義

### Enum キャスト

PHP 8.1+ の enum を使用する場合、`$casts` で自動変換が可能。

```php
<?php

declare(strict_types=1);

/**
 * @property int $id
 * @property string $title
 * @property BookFormat $format
 */
final class BookRecord extends Model
{
    protected $table = 'books';

    protected $fillable = [
        'title',
        'format',
    ];

    /**
     * キャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'format' => BookFormat::class,  // enum クラスを指定
    ];
}

// 使用例
$book = BookRecord::find(1);
$book->format;              // BookFormat::Paperback（enum インスタンス）
$book->format->value;       // 'paperback'（DB保存値）
$book->format->label();     // '文庫本'（表示用）

$book->format = BookFormat::Ebook;
$book->save();              // 'ebook' として保存
```

---

## Presentation 層

### Controller

```php
<?php

declare(strict_types=1);

final class OrderController extends Controller
{
    public function __construct(
        private PlaceOrderHandler $placeOrderHandler,
        private GetOrderHandler $getOrderHandler,
    ) {}

    public function show(int $orderId): JsonResponse
    {
        $query = new GetOrderQuery($orderId);
        $order = $this->getOrderHandler->handle($query);

        return response()->json(OrderResource::make($order));
    }

    public function place(PlaceOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            $command = new PlaceOrderCommand($orderId);
            $this->placeOrderHandler->handle($command);

            return response()->json(['status' => 'ok']);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

**ルール:**
- `final class` を使用
- コンストラクタで Handler を注入
- ビジネスロジックを書かない
- 例外ハンドリングは Controller または Handler で

### FormRequest

```php
<?php

declare(strict_types=1);

final class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 入力バリデーションのみ
            // ビジネスルールは Domain で検証
        ];
    }
}
```

### Resource

```php
<?php

declare(strict_types=1);

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OrderDTO $this->resource */
        return [
            'id' => $this->resource->id,
            'customer_id' => $this->resource->customerId,
            'status' => $this->resource->status,
            'amount' => $this->resource->amount,
        ];
    }
}
```

---

## 例外処理

### 例外クラスの分類

```php
<?php

declare(strict_types=1);

// Domain 例外（業務ルール違反）
namespace Packages\Common\Domain\Exceptions;

class DomainException extends \DomainException {}

namespace Packages\Agenda\Order\Domain\Exceptions;

final class OrderAlreadyPlacedException extends DomainException
{
    public function __construct(OrderId $orderId)
    {
        parent::__construct("注文 {$orderId->value()} は既に確定済みです");
    }
}

// Infrastructure 例外（技術的問題）
namespace Packages\Agenda\Order\Infrastructure\Exceptions;

final class OrderNotFoundException extends \RuntimeException
{
    public function __construct(OrderId $orderId)
    {
        parent::__construct("注文 {$orderId->value()} が見つかりません");
    }
}
```

### 例外ハンドリング

```php
// Handler.php（ExceptionHandler）
public function render($request, Throwable $e): Response
{
    if ($e instanceof DomainException) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 400);
    }

    if ($e instanceof ModelNotFoundException) {
        return response()->json([
            'error' => 'リソースが見つかりません',
        ], 404);
    }

    return parent::render($request, $e);
}
```

---

## コメント・ドキュメント

### PHPDoc

```php
/**
 * 注文を確定する
 *
 * @throws OrderAlreadyPlacedException 既に確定済みの場合
 * @throws InsufficientStockException 在庫が不足している場合
 */
public function place(): void
{
    // ...
}

/**
 * 顧客の注文一覧を取得する
 *
 * @return list<Order>
 */
public function findByCustomer(CustomerId $customerId): array
{
    // ...
}
```

### コメントの方針

```php
// ✓ Good: Why を説明
// 同時編集を防ぐため、悲観的ロックを使用
$record = OrderRecord::lockForUpdate()->find($id);

// ✗ Bad: What を説明（コードを読めばわかる）
// 注文を取得する
$order = $this->orderRepository->find($id);
```

---

## 禁止事項

### Domain 層

- [ ] Laravel Facade の使用禁止
- [ ] Eloquent の直接使用禁止
- [ ] `DB::` の使用禁止
- [ ] `Carbon` の直接使用禁止（`DateTimeImmutable` を使用）
- [ ] `config()`, `env()` の使用禁止

### 全般

- [ ] `dd()`, `dump()` の本番コードへのコミット禁止
- [ ] `sleep()` の使用禁止（テスト以外）
- [ ] `exit()`, `die()` の使用禁止
- [ ] `@` によるエラー抑制禁止
- [ ] `eval()` の使用禁止
- [ ] **厳密モード（`declare(strict_types=1);`）の省略禁止**

---

## 推奨ツール設定

### pint.json

```json
{
    "preset": "laravel",
    "rules": {
        "final_class": true,
        "declare_strict_types": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        }
    }
}
```

### phpstan.neon

```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - src
    level: 6
    checkMissingIterableValueType: false
```

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計標準
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計
- [04_Non-FunctionalRequirements.md](./04_Non-FunctionalRequirements.md) - 非機能要件
