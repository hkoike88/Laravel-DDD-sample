# バックエンド データベース設計標準

## 概要

本プロジェクトのバックエンドにおけるデータベース設計標準を定める。
MySQL 8.0 を使用し、一貫性のある命名規約、データ型選定、マイグレーション規約を通じて保守性と性能を確保する。

---

## 基本方針

- **一貫性**: 命名規約・データ型選定を統一し、可読性を向上
- **正規化**: 原則として第3正規形まで正規化、必要に応じて非正規化
- **パフォーマンス**: 適切なインデックス設計と ULID 採用による挿入性能の確保
- **安全性**: 外部キー制約による参照整合性の担保
- **追跡可能性**: 作成日時・更新日時の記録

---

## 命名規約

### テーブル名

| ルール | 説明 | 例 |
|--------|------|-----|
| 複数形 | エンティティの集合を表す | `books`, `users`, `loans` |
| スネークケース | 単語間はアンダースコア | `loan_histories`, `book_categories` |
| 英小文字のみ | 大文字は使用しない | `staff_members` |
| 略語は避ける | 意味が明確な完全な単語 | `categories`（`cat` ではない） |
| 中間テーブル | アルファベット順で結合 | `book_category`（`books` と `categories`） |

```php
// Good
Schema::create('books', ...);
Schema::create('loan_histories', ...);
Schema::create('book_category', ...);  // 中間テーブル

// Bad
Schema::create('Book', ...);           // 大文字
Schema::create('tbl_books', ...);      // プレフィックス不要
Schema::create('loan_hist', ...);      // 略語
```

### カラム名

| ルール | 説明 | 例 |
|--------|------|-----|
| スネークケース | 単語間はアンダースコア | `first_name`, `created_at` |
| 英小文字のみ | 大文字は使用しない | `email`, `staff_id` |
| 意味のある名前 | 目的が明確な名前 | `published_at`（`pub_date` ではない） |
| ブール値 | `is_`, `has_`, `can_` プレフィックス | `is_active`, `has_returned`, `can_reserve` |
| 外部キー | 参照テーブル名の単数形 + `_id` | `book_id`, `staff_id` |
| 日時 | `_at` サフィックス | `created_at`, `updated_at`, `borrowed_at` |
| 日付 | `_on` または `_date` サフィックス | `due_on`, `birth_date` |

```php
// Good
$table->string('email');
$table->boolean('is_active');
$table->char('book_id', 26);
$table->timestamp('borrowed_at');
$table->date('due_on');

// Bad
$table->string('Email');          // 大文字
$table->boolean('active');        // プレフィックスなし
$table->char('bookId', 26);       // キャメルケース
$table->timestamp('borrowed');    // サフィックスなし
```

### インデックス名

| 種類 | 命名パターン | 例 |
|------|-------------|-----|
| プライマリキー | 自動（`PRIMARY`） | - |
| ユニークインデックス | `{table}_{columns}_unique` | `users_email_unique` |
| 通常インデックス | `{table}_{columns}_index` | `loans_book_id_index` |
| 複合インデックス | `{table}_{col1}_{col2}_index` | `loans_user_id_status_index` |
| フルテキスト | `{table}_{columns}_fulltext` | `books_title_fulltext` |

```php
// Laravel では自動的に命名されるが、明示的な指定も可能
$table->unique('email', 'users_email_unique');
$table->index(['user_id', 'status'], 'loans_user_id_status_index');
$table->fullText('title', 'books_title_fulltext');
```

### 外部キー名

| 命名パターン | 例 |
|-------------|-----|
| `{table}_{column}_foreign` | `loans_book_id_foreign` |

```php
// Laravel の規約に従う（自動命名）
$table->foreign('book_id')->references('id')->on('books');

// または constrained() を使用
$table->foreignId('book_id')->constrained();
```

---

## データ型

### 選定基準

| 用途 | データ型 | 説明 |
|------|---------|------|
| 主キー（ULID） | `CHAR(26)` | 固定長 ULID |
| 外部キー（ULID） | `CHAR(26)` | 主キーと同じ型 |
| 短い文字列 | `VARCHAR(n)` | 名前、メール等 |
| 長い文字列 | `TEXT` | 説明、本文等 |
| 整数 | `INT` / `BIGINT` | カウント、数量 |
| 金額 | `DECIMAL(10,0)` | 円単位（小数不要） |
| 真偽値 | `TINYINT(1)` | Laravel の boolean |
| 日時 | `TIMESTAMP` | タイムゾーン対応 |
| 日付のみ | `DATE` | 年月日のみ |
| 列挙型 | `VARCHAR(20)` | ENUM より柔軟 |
| JSON | `JSON` | 構造化データ |

### ID 戦略（ULID）

本プロジェクトでは **ULID**（Universally Unique Lexicographically Sortable Identifier）を主キーとして採用する。

**採用理由:**
- 時系列ソートが可能（先頭 48 ビットがタイムスタンプ）
- URL フレンドリー（26 文字の英数字）
- B+Tree インデックスでの挿入パフォーマンスが良好
- 分散環境での一意性保証

**参照:** [ADR-0006_ID-Strategy.md](../../20_architecture/adr/ADR-0006_ID-Strategy.md)

```php
// マイグレーション
Schema::create('books', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('title', 255);
    $table->timestamps();
});

// 値オブジェクト（Domain 層）
final class BookId
{
    private function __construct(
        private readonly string $value
    ) {}

    public static function generate(): self
    {
        return new self((new Ulid())->toBase32());
    }

    public static function fromString(string $value): self
    {
        if (strlen($value) !== 26) {
            throw new InvalidArgumentException('Invalid ULID format');
        }
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

### 日時型

| 用途 | Laravel メソッド | MySQL 型 | 例 |
|------|-----------------|----------|-----|
| 作成日時 | `$table->timestamp('created_at')` | TIMESTAMP | 2024-01-01 12:00:00 |
| 更新日時 | `$table->timestamp('updated_at')` | TIMESTAMP | 2024-01-01 12:00:00 |
| 任意の日時 | `$table->timestamp('published_at')` | TIMESTAMP | 2024-01-01 12:00:00 |
| 日付のみ | `$table->date('due_on')` | DATE | 2024-01-01 |
| 時刻のみ | `$table->time('start_time')` | TIME | 09:00:00 |

```php
// タイムゾーン設定（config/app.php）
'timezone' => 'Asia/Tokyo',

// データベース接続設定（config/database.php）
'mysql' => [
    // ...
    'timezone' => '+09:00',
],
```

### 金額型

日本円を扱う場合、小数点以下は不要。

```php
// マイグレーション
$table->decimal('amount', 10, 0);        // -9,999,999,999 〜 9,999,999,999
$table->unsignedDecimal('price', 10, 0); // 0 〜 9,999,999,999

// 値オブジェクト
final class Money
{
    public function __construct(
        private readonly int $amount
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('金額は0以上である必要があります');
        }
    }

    public function value(): int
    {
        return $this->amount;
    }

    public function add(Money $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function format(): string
    {
        return '¥' . number_format($this->amount);
    }
}
```

### 列挙型（ステータス等）

MySQL の `ENUM` 型ではなく `VARCHAR` を使用する。`ENUM` はスキーマ変更が必要になるため柔軟性が低い。

```php
// マイグレーション
$table->string('status', 20)->default('draft');

// PHP での定義（Domain 層）
enum BookStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => '下書き',
            self::PUBLISHED => '公開中',
            self::ARCHIVED => 'アーカイブ',
        };
    }
}

// Eloquent モデルでのキャスト
protected $casts = [
    'status' => BookStatus::class,
];
```

---

## テーブル設計

### 共通カラム

すべてのテーブルに以下のカラムを含める。

| カラム | 型 | 説明 |
|--------|------|------|
| id | CHAR(26) | ULID 形式の主キー |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

```php
// 基本的なテーブル構造
Schema::create('books', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    // ... 業務カラム
    $table->timestamps();  // created_at, updated_at
});
```

### 削除戦略

本プロジェクトでは**物理削除を標準**とする。論理削除（`deleted_at` カラム）は使用しない。

#### 物理削除を標準とする理由

1. **データの一貫性**: 削除されたデータが残らず、クエリがシンプルになる
2. **ストレージ効率**: 不要なデータを保持しない
3. **GDPR/個人情報保護**: 削除要請に対して確実に対応可能
4. **クエリの簡潔さ**: `WHERE deleted_at IS NULL` の条件が不要

#### 削除時のデータ保持が必要な場合

削除時にデータを残す必要がある場合は、以下のアプローチを使用する。

**1. ステータスによる管理（推奨）**

削除ではなく、ステータスを「無効」「アーカイブ」等に変更する。

```php
// マイグレーション
Schema::create('users', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('email');
    $table->string('status', 20)->default('active');  // active, inactive, archived
    $table->timestamps();
});

// Eloquent モデル
class UserModel extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function archive(): void
    {
        $this->status = 'archived';
        $this->save();
    }
}

// クエリ
UserModel::active()->get();  // アクティブなユーザーのみ
```

**2. 履歴テーブルへの退避**

削除前にデータを履歴テーブルにコピーしてから物理削除する。

```php
// 削除前に履歴に記録
DB::transaction(function () use ($user) {
    UserHistoryModel::create([
        'id' => (new Ulid())->toBase32(),
        'user_id' => $user->id,
        'action' => 'deleted',
        'data' => $user->toArray(),
        'performed_by' => auth()->id(),
        'performed_at' => now(),
    ]);

    $user->delete();  // 物理削除
});
```

**3. 外部キー制約による保護**

関連データがある場合は外部キー制約で削除を防ぐ。

```php
// 貸出中のユーザーは削除不可
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('restrict');
```

#### テーブル別の削除戦略

| テーブル | 削除方式 | 理由 |
|---------|---------|------|
| users | ステータス管理 | アカウント無効化で対応、関連データは保持 |
| books | ステータス管理 | 貸出履歴との関連、廃棄ステータスで管理 |
| loans | 物理削除不可 | 監査証跡として永続保持（削除しない） |
| sessions | 物理削除 | 一時データ、保持不要 |
| password_resets | 物理削除 | セキュリティ上、速やかに削除 |

### 履歴テーブル

変更履歴を追跡する必要がある場合、履歴テーブルを作成する。

```php
// メインテーブル
Schema::create('books', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('title', 255);
    $table->string('status', 20)->default('active');  // active, archived, disposed
    $table->timestamps();
});

// 履歴テーブル
Schema::create('book_histories', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->char('book_id', 26);
    $table->string('action', 20);           // created, updated, deleted
    $table->json('old_values')->nullable(); // 変更前の値
    $table->json('new_values')->nullable(); // 変更後の値
    $table->char('changed_by', 26);         // 変更者
    $table->timestamp('changed_at');        // 変更日時

    $table->foreign('book_id')->references('id')->on('books');
    $table->index('book_id');
    $table->index('changed_at');
});
```

```php
// Eloquent Observer での履歴記録
final class BookObserver
{
    public function updated(BookModel $book): void
    {
        BookHistoryModel::create([
            'id' => (new Ulid())->toBase32(),
            'book_id' => $book->id,
            'action' => 'updated',
            'old_values' => $book->getOriginal(),
            'new_values' => $book->getAttributes(),
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }
}
```

---

## インデックス設計

### 設計指針

1. **検索条件に使用されるカラムにインデックスを設定**
2. **カーディナリティの高いカラムを優先**
3. **過剰なインデックスは避ける**（書き込み性能に影響）
4. **複合インデックスはカラム順序を考慮**

### インデックス種類

| 種類 | 用途 | Laravel メソッド |
|------|------|-----------------|
| PRIMARY | 主キー | `$table->primary()` |
| UNIQUE | 一意制約 | `$table->unique()` |
| INDEX | 検索高速化 | `$table->index()` |
| FULLTEXT | 全文検索 | `$table->fullText()` |

### インデックス設計パターン

```php
Schema::create('loans', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->char('user_id', 26);
    $table->char('book_id', 26);
    $table->string('status', 20);
    $table->timestamp('borrowed_at');
    $table->timestamp('returned_at')->nullable();
    $table->date('due_on');
    $table->timestamps();

    // 外部キー（自動的にインデックスも作成される）
    $table->foreign('user_id')->references('id')->on('users');
    $table->foreign('book_id')->references('id')->on('books');

    // 検索パターンに応じたインデックス
    $table->index('status');                      // ステータスでの検索
    $table->index('due_on');                      // 返却期限での検索
    $table->index(['user_id', 'status']);         // ユーザーのステータス別検索
    $table->index(['book_id', 'status']);         // 書籍のステータス別検索
});
```

### 複合インデックスの順序

複合インデックスはカラムの順序が重要。左から順に評価される。

```php
// WHERE user_id = ? AND status = ?
// WHERE user_id = ?
// 上記のクエリに有効
$table->index(['user_id', 'status']);

// WHERE status = ? のみには効果なし
// その場合は別途 status のインデックスが必要
$table->index('status');
```

### カバリングインデックス

クエリで必要なカラムがすべてインデックスに含まれる場合、テーブルへのアクセスが不要になる。

```php
// user_id と status のみ取得するクエリ
$loans = LoanModel::select(['user_id', 'status'])
    ->where('user_id', $userId)
    ->where('status', 'active')
    ->get();

// 上記に対応するインデックス（テーブルアクセス不要）
$table->index(['user_id', 'status']);
```

### フルテキスト検索

書籍タイトルや本文の検索にはフルテキストインデックスを使用。

```php
// マイグレーション
Schema::create('books', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('title', 255);
    $table->text('description')->nullable();
    $table->timestamps();

    $table->fullText(['title', 'description']);
});

// クエリ
$books = BookModel::whereFullText(['title', 'description'], '検索キーワード')
    ->get();
```

---

## 外部キー制約

### 基本方針

- **参照整合性を保証**: 外部キー制約を積極的に使用
- **CASCADE 削除は慎重に**: 意図しないデータ削除を防ぐ
- **RESTRICT の活用**: 関連データがある場合は削除を防ぐ

### 制約オプション

| オプション | 説明 | 使用場面 |
|-----------|------|---------|
| RESTRICT | 参照があれば削除不可 | 重要なマスタデータ |
| CASCADE | 親削除時に子も削除 | 親子の強い依存関係 |
| SET NULL | 親削除時に NULL 設定 | 緩やかな関連 |
| NO ACTION | RESTRICT と同等 | デフォルト |

```php
// 制約オプションの指定
Schema::create('loans', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->char('user_id', 26);
    $table->char('book_id', 26);
    $table->timestamps();

    // ユーザー削除時は貸出も削除（CASCADE）
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

    // 書籍削除は禁止（RESTRICT）
    $table->foreign('book_id')
        ->references('id')
        ->on('books')
        ->onDelete('restrict');
});
```

### 推奨パターン

| 関連 | onDelete | 理由 |
|------|----------|------|
| ユーザー → 貸出 | restrict | 貸出中のユーザーは削除不可 |
| 書籍 → 貸出 | restrict | 貸出中の書籍は削除不可 |
| 親カテゴリ → 子カテゴリ | cascade | 階層構造の維持 |
| ユーザー → セッション | cascade | ユーザー削除時にセッションも削除 |

---

## マイグレーション規約

### ファイル命名

```
{タイムスタンプ}_{操作}_{対象テーブル}_table.php
```

| 操作 | 説明 | 例 |
|------|------|-----|
| create | テーブル作成 | `2024_01_01_000001_create_books_table.php` |
| add | カラム追加 | `2024_01_02_000001_add_isbn_to_books_table.php` |
| modify | カラム変更 | `2024_01_03_000001_modify_title_in_books_table.php` |
| drop | カラム削除 | `2024_01_04_000001_drop_old_column_from_books_table.php` |
| rename | カラム名変更 | `2024_01_05_000001_rename_name_to_title_in_books_table.php` |

### 記述ルール

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションの実行
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // 主キー
            $table->char('id', 26)->primary();

            // 業務カラム（論理的なグループごとに）
            $table->string('title', 255);
            $table->string('author', 255);
            $table->string('isbn', 13)->nullable();
            $table->text('description')->nullable();

            // ステータス・フラグ
            $table->string('status', 20)->default('draft');
            $table->boolean('is_available')->default(true);

            // 外部キー
            $table->char('category_id', 26)->nullable();

            // タイムスタンプ
            $table->timestamps();

            // インデックス
            $table->unique('isbn');
            $table->index('status');
            $table->index('category_id');
            $table->fullText('title');

            // 外部キー制約
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    /**
     * マイグレーションのロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
```

### ロールバック対応

**すべてのマイグレーションにロールバック処理を実装する。**

```php
// Good: ロールバック可能
public function up(): void
{
    Schema::table('books', function (Blueprint $table) {
        $table->string('isbn', 13)->nullable()->after('author');
    });
}

public function down(): void
{
    Schema::table('books', function (Blueprint $table) {
        $table->dropColumn('isbn');
    });
}

// Bad: ロールバック不可（データ消失の可能性）
public function down(): void
{
    // 何もしない、または例外をスロー
    throw new Exception('This migration cannot be rolled back');
}
```

### 本番環境での変更

本番環境でのスキーマ変更は慎重に行う。

```php
// 大量データのあるテーブルへのカラム追加
// ロック時間を最小化するためのパターン

// Step 1: NULL 許容でカラム追加（高速）
public function up(): void
{
    Schema::table('large_table', function (Blueprint $table) {
        $table->string('new_column')->nullable()->after('existing_column');
    });
}

// Step 2: バッチ処理でデータ移行（別マイグレーション）
public function up(): void
{
    DB::table('large_table')
        ->whereNull('new_column')
        ->chunkById(1000, function ($records) {
            foreach ($records as $record) {
                DB::table('large_table')
                    ->where('id', $record->id)
                    ->update(['new_column' => $this->calculateValue($record)]);
            }
        });
}

// Step 3: NOT NULL 制約追加（別マイグレーション）
public function up(): void
{
    Schema::table('large_table', function (Blueprint $table) {
        $table->string('new_column')->nullable(false)->change();
    });
}
```

---

## シーディング規約

### ファイル構成

```
database/seeders/
├── DatabaseSeeder.php          # エントリポイント
├── MasterDataSeeder.php        # マスタデータ（本番も使用）
├── DevelopmentSeeder.php       # 開発用ダミーデータ
└── Testing/
    └── BookSeeder.php          # テスト用データ
```

### マスタデータシーダー

本番環境でも使用するマスタデータ。

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

final class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedRoles();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => '文学', 'code' => 'LITERATURE'],
            ['name' => '科学', 'code' => 'SCIENCE'],
            ['name' => '歴史', 'code' => 'HISTORY'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['code' => $category['code']],
                [
                    'id' => (new Ulid())->toBase32(),
                    'name' => $category['name'],
                    'code' => $category['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => '管理者', 'code' => 'admin'],
            ['name' => '職員', 'code' => 'staff'],
            ['name' => '利用者', 'code' => 'user'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'id' => (new Ulid())->toBase32(),
                    'name' => $role['name'],
                    'code' => $role['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
```

### 開発用シーダー

開発環境でのみ使用するダミーデータ。

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BookModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;

final class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // Factory を使用してダミーデータ生成
        UserModel::factory()
            ->count(50)
            ->create();

        BookModel::factory()
            ->count(100)
            ->create();
    }
}
```

### DatabaseSeeder

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // マスタデータは常に実行
        $this->call(MasterDataSeeder::class);

        // 開発環境のみダミーデータを投入
        if (app()->environment('local', 'development')) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
```

---

## クエリ最適化

### N+1 問題の回避

```php
// Bad: N+1 問題
$loans = LoanModel::all();
foreach ($loans as $loan) {
    echo $loan->user->name;  // 各ループで追加クエリ
    echo $loan->book->title;
}

// Good: Eager Loading
$loans = LoanModel::with(['user', 'book'])->get();
foreach ($loans as $loan) {
    echo $loan->user->name;  // 追加クエリなし
    echo $loan->book->title;
}
```

### 必要なカラムのみ取得

```php
// Bad: 全カラム取得
$books = BookModel::all();

// Good: 必要なカラムのみ
$books = BookModel::select(['id', 'title', 'author'])->get();
```

### 大量データ処理

```php
// Bad: 全件メモリに読み込み
$users = UserModel::all();
foreach ($users as $user) {
    // 処理
}

// Good: チャンク処理
UserModel::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // 処理
    }
});

// Good: カーソル（さらにメモリ効率が良い）
foreach (UserModel::cursor() as $user) {
    // 処理
}
```

### 集計クエリ

```php
// Bad: PHP で集計
$total = BookModel::all()->sum('price');

// Good: データベースで集計
$total = BookModel::sum('price');

// Good: 複数の集計
$stats = BookModel::selectRaw('
    COUNT(*) as total_count,
    SUM(price) as total_price,
    AVG(price) as average_price
')->first();
```

---

## チェックリスト

### テーブル設計

- [ ] テーブル名は複数形・スネークケースか
- [ ] すべてのテーブルに id（ULID）、created_at、updated_at があるか
- [ ] カラム名は意味が明確か
- [ ] 適切なデータ型を選択しているか
- [ ] NOT NULL 制約は適切か
- [ ] デフォルト値は設定されているか

### インデックス

- [ ] 主キーが設定されているか
- [ ] 検索条件に使用されるカラムにインデックスがあるか
- [ ] 複合インデックスの順序は適切か
- [ ] 不要なインデックスはないか
- [ ] フルテキスト検索が必要な場合、FULLTEXT インデックスがあるか

### 外部キー

- [ ] 参照整合性が必要な関連に外部キー制約があるか
- [ ] onDelete オプションは適切か
- [ ] 循環参照はないか

### マイグレーション

- [ ] ファイル名は規約に従っているか
- [ ] up() と down() の両方が実装されているか
- [ ] ロールバック可能か
- [ ] 本番環境での実行時間を考慮しているか

### パフォーマンス

- [ ] N+1 問題は発生していないか
- [ ] 適切な Eager Loading を使用しているか
- [ ] 大量データ処理にはチャンク/カーソルを使用しているか
- [ ] 集計はデータベースで行っているか

---

## 関連ドキュメント

- [ADR-0003_Database.md](../../20_architecture/adr/ADR-0003_Database.md) - データベース選定
- [ADR-0006_ID-Strategy.md](../../20_architecture/adr/ADR-0006_ID-Strategy.md) - ID 生成戦略
- [01_ArchitectureDesign](../../20_architecture/backend/01_ArchitectureDesign/) - アーキテクチャ設計標準
- [01_CodingStandards.md](./01_CodingStandards.md) - コーディング規約
- [03_Non-FunctionalRequirements.md](./03_Non-FunctionalRequirements.md) - 非機能要件
- [09_TransactionDesign.md](./09_TransactionDesign.md) - トランザクション設計標準
- [10_TransactionConsistencyDesign.md](./10_TransactionConsistencyDesign.md) - トランザクション整合性保証設計

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-25 | 初版作成 |
