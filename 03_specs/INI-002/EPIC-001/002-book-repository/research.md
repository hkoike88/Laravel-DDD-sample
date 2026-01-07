# Research: 蔵書リポジトリ実装

**Date**: 2025-12-24
**Feature**: 002-book-repository
**Status**: Complete

---

## 1. 既存コードベース分析

### 1.1 依存する実装（001-book-entity-design）

#### Book エンティティ

`backend/packages/Domain/Book/Domain/Model/Book.php`

- **create()**: 新規蔵書作成（ステータスは available で固定）
- **reconstruct()**: 永続化データからの復元（ステータス指定可能）
- 属性: id, title, author, isbn, publisher, publishedYear, genre, status
- 状態遷移メソッド: borrow(), return(), reserve(), lendToReserver(), cancelReservation()

#### Value Objects

| クラス | ファイル | 永続化形式 |
|--------|----------|------------|
| BookId | ValueObjects/BookId.php | ULID 文字列（26文字） |
| ISBN | ValueObjects/ISBN.php | ハイフンなし正規化形式 |
| BookStatus | ValueObjects/BookStatus.php | 文字列（available/borrowed/reserved） |

#### 既存 BookRepositoryInterface

```php
interface BookRepositoryInterface
{
    public function find(BookId $id): Book;
    public function findOrNull(BookId $id): ?Book;
    public function findByIsbn(ISBN $isbn): array;
    public function save(Book $book): void;
    public function delete(BookId $id): void;
}
```

### 1.2 拡張が必要な機能

仕様書（spec.md）の要件に基づき、以下の拡張が必要:

1. **条件検索**: タイトル、著者、出版社（部分一致）、ジャンル、ステータス（完全一致）、出版年（範囲）
2. **ページネーション**: ページ番号、ページサイズ、総件数、総ページ数
3. **ソート**: タイトル、出版年（昇順/降順）
4. **件数取得**: 検索条件に一致する件数を効率的に取得

---

## 2. Laravel Eloquent リポジトリ実装パターン

### 2.1 Eloquent モデル（BookRecord）

プロジェクト標準（02_実装パターン.md）に従い、Eloquent モデルは `{Name}Record` 命名規則を使用:

```php
// Infrastructure/EloquentModels/BookRecord.php
class BookRecord extends Model
{
    protected $table = 'books';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'author', 'isbn',
        'publisher', 'published_year', 'genre', 'status'
    ];
}
```

### 2.2 リポジトリ実装パターン

```php
// Application/Repositories/EloquentBookRepository.php
class EloquentBookRepository implements BookRepositoryInterface
{
    public function find(BookId $id): Book
    {
        $record = BookRecord::findOrFail($id->value());
        return $this->toDomain($record);
    }

    public function save(Book $book): void
    {
        $record = BookRecord::findOrNew($book->id()->value());
        $record->id = $book->id()->value();
        $record->title = $book->title();
        // ... 他の属性
        $record->save();
    }

    private function toDomain(BookRecord $record): Book
    {
        return Book::reconstruct(
            id: BookId::fromString($record->id),
            title: $record->title,
            // ... 他の属性
        );
    }
}
```

### 2.3 検索条件の実装

#### Query Builder パターン

```php
public function search(BookSearchCriteria $criteria): BookCollection
{
    $query = BookRecord::query();

    // 部分一致検索
    if ($criteria->title !== null) {
        $query->where('title', 'LIKE', "%{$criteria->title}%");
    }

    // 完全一致検索
    if ($criteria->status !== null) {
        $query->where('status', $criteria->status->value());
    }

    // 範囲検索
    if ($criteria->publishedYearFrom !== null) {
        $query->where('published_year', '>=', $criteria->publishedYearFrom);
    }

    // ソート
    $query->orderBy($criteria->sortField, $criteria->sortDirection);

    // ページネーション
    $paginator = $query->paginate(
        perPage: $criteria->pageSize,
        page: $criteria->page
    );

    return new BookCollection(
        items: collect($paginator->items())
            ->map(fn($r) => $this->toDomain($r))
            ->all(),
        totalCount: $paginator->total(),
        currentPage: $paginator->currentPage(),
        totalPages: $paginator->lastPage(),
    );
}
```

---

## 3. データベース設計

### 3.1 books テーブル

```sql
CREATE TABLE books (
    id VARCHAR(26) PRIMARY KEY,          -- ULID
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NULL,
    isbn VARCHAR(13) NULL,               -- ハイフンなし正規化
    publisher VARCHAR(255) NULL,
    published_year SMALLINT UNSIGNED NULL,
    genre VARCHAR(100) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_books_isbn (isbn),
    INDEX idx_books_title (title),
    INDEX idx_books_author (author),
    INDEX idx_books_status (status),
    INDEX idx_books_published_year (published_year)
);
```

### 3.2 インデックス戦略

| カラム | インデックスタイプ | 理由 |
|--------|-------------------|------|
| id | PRIMARY KEY | ULID による一意識別 |
| isbn | INDEX | ISBN 検索の高速化 |
| title | INDEX | タイトル部分一致検索 |
| author | INDEX | 著者部分一致検索 |
| status | INDEX | ステータス絞り込み |
| published_year | INDEX | 出版年範囲検索 |

### 3.3 パフォーマンス考慮

- **LIKE 検索の最適化**: 前方一致（`title%`）はインデックス使用可能、中間一致（`%title%`）は使用不可
- **複合インデックス**: 頻繁な組み合わせ検索がある場合は後続で追加検討
- **楽観的ロック**: `updated_at` を使用した競合検出（仕様書の Edge Case 対応）

---

## 4. 検索条件 DTO 設計

### 4.1 BookSearchCriteria

```php
final class BookSearchCriteria
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $author = null,
        public readonly ?string $publisher = null,
        public readonly ?string $genre = null,
        public readonly ?BookStatus $status = null,
        public readonly ?int $publishedYearFrom = null,
        public readonly ?int $publishedYearTo = null,
        public readonly int $page = 1,
        public readonly int $pageSize = 20,
        public readonly string $sortField = 'title',
        public readonly string $sortDirection = 'asc',
    ) {
        // pageSize は 1-100 の範囲
        // sortField は許可されたフィールドのみ
    }
}
```

### 4.2 BookCollection

```php
final class BookCollection
{
    /**
     * @param list<Book> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $totalCount,
        public readonly int $currentPage,
        public readonly int $totalPages,
    ) {}

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }
}
```

---

## 5. 例外設計

### 5.1 BookNotFoundException

`find()` メソッドで蔵書が見つからない場合にスロー:

```php
final class BookNotFoundException extends RuntimeException
{
    public function __construct(BookId $id)
    {
        parent::__construct(
            "蔵書が見つかりません: {$id->value()}"
        );
    }
}
```

---

## 6. テスト戦略

### 6.1 統合テスト（EloquentBookRepositoryTest）

```php
class EloquentBookRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_蔵書を保存して取得できる(): void
    {
        $book = Book::create(
            id: BookId::generate(),
            title: 'テスト書籍',
            // ...
        );

        $this->repository->save($book);
        $found = $this->repository->find($book->id());

        $this->assertTrue($book->id()->equals($found->id()));
    }

    public function test_ISBNで複本を検索できる(): void
    {
        // 同一ISBNの蔵書を3冊作成
        // ISBN検索で3冊取得できることを確認
    }

    public function test_条件検索でページネーションできる(): void
    {
        // 複数蔵書を作成
        // ページサイズ指定で検索
        // 正しいページ情報が返ることを確認
    }
}
```

### 6.2 単体テスト（DTO）

```php
class BookSearchCriteriaTest extends TestCase
{
    public function test_デフォルト値が正しい(): void
    {
        $criteria = new BookSearchCriteria();

        $this->assertSame(1, $criteria->page);
        $this->assertSame(20, $criteria->pageSize);
        $this->assertSame('title', $criteria->sortField);
        $this->assertSame('asc', $criteria->sortDirection);
    }

    public function test_ページサイズは最大100(): void
    {
        // 100を超える値は100に制限されることを確認
    }
}
```

---

## 7. 実装順序の提案

### Phase 1: 基盤

1. マイグレーション作成（books テーブル）
2. BookRecord Eloquent モデル作成
3. BookNotFoundException 作成

### Phase 2: 基本リポジトリ

4. EloquentBookRepository 基本実装（save, find, findOrNull, findByIsbn, delete）
5. BookServiceProvider にバインディング追加
6. 統合テスト作成

### Phase 3: 検索機能

7. BookSearchCriteria DTO 作成
8. BookCollection DTO 作成
9. search() メソッド実装
10. count() メソッド実装

### Phase 4: 検証

11. 全テスト実行・カバレッジ確認
12. パフォーマンステスト（1万件データ）

---

## 8. リスクと対策

| リスク | 対策 |
|--------|------|
| LIKE 検索のパフォーマンス | 前方一致優先、必要に応じて全文検索導入 |
| 同時更新の競合 | 楽観的ロック（updated_at チェック） |
| ISBN null の検索漏れ | null 値は ISBN 検索結果に含めない |
| 長いタイトル | 255文字制限、超過時は切り詰め |

---

## 9. 参考資料

- プロジェクト標準: `00_docs/20_tech/99_standard/backend/01_ArchitectureDesign/`
- 依存機能仕様: `specs/001-book-entity-design/quickstart.md`
- 本機能仕様: `specs/002-book-repository/spec.md`
