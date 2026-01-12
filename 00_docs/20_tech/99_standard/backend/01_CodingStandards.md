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
- `toDomain()` で Eloquent → Domain 変換（単一エンティティ）
- `save()` で Domain → Eloquent 変換

### Repository - 配列返却パターン

複数のエンティティを配列で返却する場合。

```php
<?php

declare(strict_types=1);

final class EloquentBookRepository implements BookRepositoryInterface
{
    /**
     * ISBN で蔵書を検索（複本対応）
     *
     * @param  ISBN  $isbn  ISBN
     * @return list<Book> 蔵書エンティティのリスト
     */
    public function findByIsbn(ISBN $isbn): array
    {
        $records = BookRecord::where('isbn', $isbn->value())->get();

        return array_values($records->map(fn (BookRecord $record) => $this->toDomain($record))->all());
    }

    private function toDomain(BookRecord $record): Book
    {
        return Book::reconstruct(
            id: BookId::fromString($record->id),
            title: $record->title,
            author: $record->author,
            isbn: $record->isbn !== null ? ISBN::fromString($record->isbn) : null,
            publisher: $record->publisher,
            publishedYear: $record->published_year,
            genre: $record->genre,
            status: BookStatus::from($record->status),
            registeredBy: $record->registered_by,
            registeredAt: $record->registered_at !== null
                ? \DateTimeImmutable::createFromInterface($record->registered_at)
                : null,
        );
    }
}
```

**ルール:**
- `array_values()` でインデックスをリセット
- `map()` で各レコードを `toDomain()` でドメインモデルに変換
- 返却型は `list<Entity>` の配列

### Repository - Collection DTO 返却パターン

ページネーション付きコレクションを返却する場合、Collection DTO を使用する。

```php
<?php

declare(strict_types=1);

final class EloquentBookRepository implements BookRepositoryInterface
{
    /**
     * 条件で蔵書を検索
     *
     * @param  BookSearchCriteria  $criteria  検索条件
     * @return BookCollection 検索結果コレクション
     */
    public function search(BookSearchCriteria $criteria): BookCollection
    {
        $query = BookRecord::query();

        $this->applySearchCriteria($query, $criteria);

        // 総件数を取得
        $totalCount = $query->count();

        if ($totalCount === 0) {
            return BookCollection::empty($criteria->pageSize);
        }

        // ソートを適用
        $sortField = $this->mapSortField($criteria->sortField);
        $query->orderBy($sortField, $criteria->sortDirection);

        // ページネーションを適用
        $records = $query
            ->offset($criteria->offset())
            ->limit($criteria->pageSize)
            ->get();

        // ドメインモデルに変換
        $items = array_values($records->map(fn (BookRecord $record) => $this->toDomain($record))->all());

        // 総ページ数を計算
        $totalPages = (int) ceil($totalCount / $criteria->pageSize);

        return new BookCollection(
            items: $items,
            totalCount: $totalCount,
            currentPage: $criteria->page,
            totalPages: $totalPages,
            pageSize: $criteria->pageSize,
        );
    }

    /**
     * 検索条件をクエリに適用
     *
     * @param  Builder<BookRecord>  $query  クエリビルダー
     * @param  BookSearchCriteria  $criteria  検索条件
     */
    private function applySearchCriteria(Builder $query, BookSearchCriteria $criteria): void
    {
        // タイトル（部分一致）
        if ($criteria->title !== null) {
            $query->where('title', 'LIKE', '%'.$criteria->title.'%');
        }

        // 著者（部分一致）
        if ($criteria->author !== null) {
            $query->where('author', 'LIKE', '%'.$criteria->author.'%');
        }

        // ISBN（完全一致）
        if ($criteria->isbn !== null) {
            $query->where('isbn', $criteria->isbn);
        }
    }

    private function toDomain(BookRecord $record): Book
    {
        return Book::reconstruct(/* ... */);
    }
}
```

**ルール:**
- ページネーション情報（総件数、総ページ数等）は Repository で計算
- 空のコレクションは DTO のファクトリメソッド `empty()` を使用
- ソート条件も Repository で適用
- クエリ条件の適用は private メソッド `applySearchCriteria()` に分離

### Application 層 - Collection DTO

ページネーション付きコレクションを表現する DTO。

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\DTO;

use Packages\Domain\Book\Domain\Model\Book;

/**
 * 蔵書コレクション
 *
 * 検索結果の蔵書リストとページネーション情報を保持。
 */
final readonly class BookCollection
{
    /**
     * コンストラクタ
     *
     * @param  list<Book>  $items  蔵書リスト
     * @param  int  $totalCount  総件数
     * @param  int  $currentPage  現在のページ番号
     * @param  int  $totalPages  総ページ数
     * @param  int  $pageSize  ページサイズ
     */
    public function __construct(
        public array $items,
        public int $totalCount,
        public int $currentPage,
        public int $totalPages,
        public int $pageSize,
    ) {}

    /**
     * 空のコレクションを生成
     *
     * @param  int  $pageSize  ページサイズ
     */
    public static function empty(int $pageSize = 20): self
    {
        return new self(
            items: [],
            totalCount: 0,
            currentPage: 1,
            totalPages: 0,
            pageSize: $pageSize,
        );
    }

    /**
     * コレクションが空か判定
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * 次のページが存在するか判定
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * 前のページが存在するか判定
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * 蔵書の件数を取得
     */
    public function count(): int
    {
        return count($this->items);
    }
}
```

**ルール:**
- `readonly` クラスを使用（イミュータブル）
- 命名は `{Name}Collection`
- ページネーション情報を含む
- ヘルパーメソッド（`isEmpty()`, `hasNextPage()` 等）を提供
- ファクトリメソッド `empty()` を提供
- Application 層の DTO ディレクトリに配置

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

    /**
     * 注文を確定する
     *
     * 例外は ExceptionHandler で自動的に処理される
     * - DomainException → 400 Bad Request
     * - その他の ApplicationException → 適切な HTTP ステータスコード
     */
    public function place(PlaceOrderRequest $request, int $orderId): JsonResponse
    {
        $command = new PlaceOrderCommand($orderId);
        $this->placeOrderHandler->handle($command);

        return response()->json(['status' => 'ok']);
    }
}
```

**ルール:**
- `final class` を使用
- コンストラクタで Handler を注入
- ビジネスロジックを書かない
- **例外処理は原則として ExceptionHandler で自動処理（Controller で try-catch を使わない）**
- メソッドは短く保つ（目安: 20行以内）

**例外処理の方針:**

原則として、Controller では例外をキャッチせず、`ExceptionHandler`（`app/Exceptions/Handler.php`）で自動処理する。

```php
// ✓ Good: 例外を ExceptionHandler に委譲
public function place(PlaceOrderRequest $request, int $orderId): JsonResponse
{
    // 例外は ExceptionHandler で自動的に処理される
    $command = new PlaceOrderCommand($orderId);
    $this->placeOrderHandler->handle($command);

    return response()->json(['status' => 'ok']);
}
```

**例外的なケース:**

以下の場合のみ、Controller で try-catch を使用する：

1. **リソースクリーンアップが必要な場合**
   ```php
   $lock = $this->acquireLock($orderId);
   try {
       $command = new PlaceOrderCommand($orderId);
       $this->placeOrderHandler->handle($command);
       return response()->json(['status' => 'ok']);
   } finally {
       $lock->release(); // 必ずロック解放
   }
   ```

2. **一時ファイルなどの確実な削除が必要な場合**
   ```php
   $tempFile = $this->createTempFile($request->file('upload'));
   try {
       $command = new ProcessFileCommand($tempFile);
       $result = $this->handler->handle($command);
       return response()->json(['data' => $result]);
   } finally {
       unlink($tempFile); // 必ず一時ファイルを削除
   }
   ```

**理由:**
- 例外処理の一元化により、一貫性が保たれる
- Controller の責務が明確になる（HTTP リクエスト/レスポンスの処理に集中）
- エラーレスポンス形式の統一が容易
- ログ記録の一元管理が可能

詳細は [`07_ErrorHandling.md`](./07_ErrorHandling.md) の「Controller での例外処理」セクションを参照

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

### FormRequest からの入力取得

**原則: コントローラでは `$request->input()` を直接呼び出さず、FormRequest に定義した型付きメソッド経由で入力値を取得する。**

#### 必須パラメータ

バリデーションで `required` を指定したパラメータは、non-null の型で返すメソッドを定義する。

```php
<?php

declare(strict_types=1);

final class CreateBookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * タイトルを取得
     *
     * @return string タイトル（必ず存在する）
     */
    public function title(): string
    {
        return $this->input('title');
    }
}
```

#### オプショナルパラメータ

バリデーションで `nullable` を指定したパラメータは、nullable 型で返すメソッドを定義する。

```php
<?php

declare(strict_types=1);

final class SearchBooksRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * タイトル検索条件を取得
     *
     * @return string|null タイトル（nullの場合は条件指定なし）
     */
    public function title(): ?string
    {
        return $this->input('title');
    }
}
```

#### デフォルト値付きパラメータ

デフォルト値がある場合は、non-null 型で返し、PHPDoc でデフォルト値を明記する。

```php
<?php

declare(strict_types=1);

final class ListBooksRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * ページ番号を取得
     *
     * @return int ページ番号（デフォルト: 1）
     */
    public function page(): int
    {
        return (int) ($this->input('page') ?? 1);
    }
}
```

#### コントローラでの使用

```php
// ✓ Good: FormRequest のメソッド経由で取得
public function store(CreateBookRequest $request): JsonResponse
{
    $command = new CreateBookCommand(
        title: $request->title(),
        author: $request->author(),
        isbn: $request->isbn(),
    );
    // ...
}

// ✗ Bad: input() の直接呼び出し
public function store(CreateBookRequest $request): JsonResponse
{
    $command = new CreateBookCommand(
        title: $request->input('title'),      // ✗ 型安全性が低い
        author: $request->input('author'),    // ✗ typo のリスクがある
        isbn: $request->input('isbn'),        // ✗ PHPDoc との整合性が取りにくい
    );
    // ...
}
```

**ルール:**
- FormRequest に定義した各入力パラメータに対応する型付きメソッドを作成する
- メソッド名は入力キー名に対応させる（キャメルケース）
- 戻り値の型は、バリデーションルールに応じて設定する（required → non-null、nullable → nullable）
- PHPDoc でパラメータの説明とデフォルト値を明記する
- コントローラでは `input()` の直接呼び出しを禁止する

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
