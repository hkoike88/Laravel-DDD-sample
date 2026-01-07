# Data Model: 蔵書リポジトリ

**Date**: 2025-12-24
**Feature**: 002-book-repository

---

## 1. データベーススキーマ

### 1.1 books テーブル

```sql
CREATE TABLE books (
    -- 主キー（ULID形式、26文字固定長）
    id VARCHAR(26) NOT NULL PRIMARY KEY,

    -- 書誌情報
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NULL,
    isbn VARCHAR(13) NULL,              -- ハイフンなし正規化形式
    publisher VARCHAR(255) NULL,
    published_year SMALLINT UNSIGNED NULL,
    genre VARCHAR(100) NULL,

    -- 状態管理
    status VARCHAR(20) NOT NULL DEFAULT 'available',

    -- タイムスタンプ
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- インデックス
    INDEX idx_books_isbn (isbn),
    INDEX idx_books_title (title(100)),      -- 部分インデックス（最初の100文字）
    INDEX idx_books_author (author(100)),    -- 部分インデックス
    INDEX idx_books_status (status),
    INDEX idx_books_genre (genre),
    INDEX idx_books_published_year (published_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 1.2 カラム詳細

| カラム名 | 型 | NULL | デフォルト | 説明 |
|----------|-----|------|-----------|------|
| id | VARCHAR(26) | NO | - | ULID形式の蔵書ID |
| title | VARCHAR(255) | NO | - | 書籍タイトル |
| author | VARCHAR(255) | YES | NULL | 著者名 |
| isbn | VARCHAR(13) | YES | NULL | ISBN（ハイフンなし正規化） |
| publisher | VARCHAR(255) | YES | NULL | 出版社名 |
| published_year | SMALLINT UNSIGNED | YES | NULL | 出版年（1000-9999） |
| genre | VARCHAR(100) | YES | NULL | ジャンル |
| status | VARCHAR(20) | NO | 'available' | 貸出状態 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | 更新日時 |

### 1.3 status カラムの有効値

| 値 | 説明 |
|----|------|
| available | 利用可能（貸出・予約可能） |
| borrowed | 貸出中 |
| reserved | 予約中 |

---

## 2. Laravel マイグレーション

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 蔵書テーブルを作成
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // 主キー（ULID）
            $table->string('id', 26)->primary();

            // 書誌情報
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 13)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('genre', 100)->nullable();

            // 状態管理
            $table->string('status', 20)->default('available');

            // タイムスタンプ
            $table->timestamps();

            // インデックス
            $table->index('isbn');
            $table->index('status');
            $table->index('genre');
            $table->index('published_year');
        });

        // 部分インデックス（MySQL特有の構文）
        DB::statement('CREATE INDEX idx_books_title ON books (title(100))');
        DB::statement('CREATE INDEX idx_books_author ON books (author(100))');
    }

    /**
     * 蔵書テーブルを削除
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
```

---

## 3. Eloquent モデル

### 3.1 BookRecord

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Infrastructure\EloquentModels;

use Illuminate\Database\Eloquent\Model;

/**
 * 蔵書 Eloquent モデル
 *
 * データベーステーブルとのマッピングに専念。
 * ビジネスロジックは持たない。
 *
 * @property string $id
 * @property string $title
 * @property string|null $author
 * @property string|null $isbn
 * @property string|null $publisher
 * @property int|null $published_year
 * @property string|null $genre
 * @property string $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class BookRecord extends Model
{
    /**
     * テーブル名
     */
    protected $table = 'books';

    /**
     * 主キーの型
     */
    protected $keyType = 'string';

    /**
     * 主キーは自動インクリメントではない
     */
    public $incrementing = false;

    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'id',
        'title',
        'author',
        'isbn',
        'publisher',
        'published_year',
        'genre',
        'status',
    ];

    /**
     * キャスト定義
     */
    protected $casts = [
        'published_year' => 'integer',
    ];
}
```

---

## 4. ドメインモデルとのマッピング

### 4.1 永続化（Domain → Eloquent）

| Domain Property | Eloquent Column | 変換 |
|-----------------|-----------------|------|
| Book::id() | id | BookId::value() |
| Book::title() | title | そのまま |
| Book::author() | author | そのまま（null許容） |
| Book::isbn() | isbn | ISBN::value()（null許容） |
| Book::publisher() | publisher | そのまま（null許容） |
| Book::publishedYear() | published_year | そのまま（null許容） |
| Book::genre() | genre | そのまま（null許容） |
| Book::status() | status | BookStatus::value() |

### 4.2 復元（Eloquent → Domain）

```php
private function toDomain(BookRecord $record): Book
{
    return Book::reconstruct(
        id: BookId::fromString($record->id),
        title: $record->title,
        author: $record->author,
        isbn: $record->isbn !== null
            ? ISBN::fromString($record->isbn)
            : null,
        publisher: $record->publisher,
        publishedYear: $record->published_year,
        genre: $record->genre,
        status: BookStatus::from($record->status),
    );
}
```

---

## 5. 検索条件 DTO

### 5.1 BookSearchCriteria

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\DTO;

use InvalidArgumentException;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;

/**
 * 蔵書検索条件
 *
 * 蔵書検索のパラメータを保持する不変オブジェクト。
 */
final readonly class BookSearchCriteria
{
    /**
     * 許可されたソートフィールド
     */
    private const ALLOWED_SORT_FIELDS = [
        'title',
        'author',
        'published_year',
        'created_at',
    ];

    /**
     * 許可されたソート方向
     */
    private const ALLOWED_SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * デフォルトページサイズ
     */
    public const DEFAULT_PAGE_SIZE = 20;

    /**
     * 最大ページサイズ
     */
    public const MAX_PAGE_SIZE = 100;

    /**
     * コンストラクタ
     *
     * @param string|null $title タイトル（部分一致）
     * @param string|null $author 著者（部分一致）
     * @param string|null $publisher 出版社（部分一致）
     * @param string|null $genre ジャンル（完全一致）
     * @param BookStatus|null $status ステータス（完全一致）
     * @param int|null $publishedYearFrom 出版年（From）
     * @param int|null $publishedYearTo 出版年（To）
     * @param int $page ページ番号（1始まり）
     * @param int $pageSize ページサイズ
     * @param string $sortField ソートフィールド
     * @param string $sortDirection ソート方向
     */
    public function __construct(
        public ?string $title = null,
        public ?string $author = null,
        public ?string $publisher = null,
        public ?string $genre = null,
        public ?BookStatus $status = null,
        public ?int $publishedYearFrom = null,
        public ?int $publishedYearTo = null,
        public int $page = 1,
        public int $pageSize = self::DEFAULT_PAGE_SIZE,
        public string $sortField = 'title',
        public string $sortDirection = 'asc',
    ) {
        // バリデーション
        if ($page < 1) {
            throw new InvalidArgumentException('ページ番号は1以上である必要があります');
        }

        if ($pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new InvalidArgumentException(
                sprintf('ページサイズは1〜%dの範囲である必要があります', self::MAX_PAGE_SIZE)
            );
        }

        if (!in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
            throw new InvalidArgumentException(
                sprintf('無効なソートフィールド: %s', $sortField)
            );
        }

        if (!in_array($sortDirection, self::ALLOWED_SORT_DIRECTIONS, true)) {
            throw new InvalidArgumentException(
                sprintf('無効なソート方向: %s', $sortDirection)
            );
        }
    }

    /**
     * 検索条件が空か判定
     */
    public function isEmpty(): bool
    {
        return $this->title === null
            && $this->author === null
            && $this->publisher === null
            && $this->genre === null
            && $this->status === null
            && $this->publishedYearFrom === null
            && $this->publishedYearTo === null;
    }

    /**
     * オフセットを計算
     */
    public function offset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }
}
```

### 5.2 BookCollection

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
     * @param list<Book> $items 蔵書リスト
     * @param int $totalCount 総件数
     * @param int $currentPage 現在のページ番号
     * @param int $totalPages 総ページ数
     * @param int $pageSize ページサイズ
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
     */
    public static function empty(int $pageSize = BookSearchCriteria::DEFAULT_PAGE_SIZE): self
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

---

## 6. リポジトリインターフェース拡張

### 6.1 拡張後の BookRepositoryInterface

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Domain\Repositories;

use Packages\Domain\Book\Application\DTO\BookCollection;
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

/**
 * 蔵書リポジトリインターフェース
 */
interface BookRepositoryInterface
{
    /**
     * IDで蔵書を取得
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\BookNotFoundException
     */
    public function find(BookId $id): Book;

    /**
     * IDで蔵書を取得（存在しない場合はnull）
     */
    public function findOrNull(BookId $id): ?Book;

    /**
     * ISBNで蔵書を検索（複本対応）
     *
     * @return list<Book>
     */
    public function findByIsbn(ISBN $isbn): array;

    /**
     * 条件で蔵書を検索
     */
    public function search(BookSearchCriteria $criteria): BookCollection;

    /**
     * 条件に一致する蔵書の件数を取得
     */
    public function count(BookSearchCriteria $criteria): int;

    /**
     * 蔵書を保存
     */
    public function save(Book $book): void;

    /**
     * 蔵書を削除
     */
    public function delete(BookId $id): void;
}
```

---

## 7. ER図

```
+------------------+
|      books       |
+------------------+
| PK id (ULID)     |
|    title         |
|    author        |
|    isbn          |
|    publisher     |
|    published_year|
|    genre         |
|    status        |
|    created_at    |
|    updated_at    |
+------------------+
      |
      | (将来の拡張)
      v
+------------------+     +------------------+
|    loans         |     |   reservations   |
+------------------+     +------------------+
| PK id            |     | PK id            |
| FK book_id       |     | FK book_id       |
| FK user_id       |     | FK user_id       |
|    borrowed_at   |     |    reserved_at   |
|    due_date      |     |    expires_at    |
|    returned_at   |     |    status        |
+------------------+     +------------------+
```

---

## 8. インデックス設計の根拠

| インデックス | 対象クエリ | 期待される効果 |
|-------------|-----------|---------------|
| PRIMARY (id) | findById | O(1) アクセス |
| idx_isbn | findByIsbn | ISBN 検索の高速化 |
| idx_title | タイトル部分一致 | 前方一致時にインデックス使用 |
| idx_author | 著者部分一致 | 前方一致時にインデックス使用 |
| idx_status | ステータスフィルタ | 絞り込みの高速化 |
| idx_genre | ジャンルフィルタ | 絞り込みの高速化 |
| idx_published_year | 出版年範囲検索 | 範囲クエリの最適化 |

---

## 9. データ整合性ルール

1. **id**: ULID形式（26文字）、一意
2. **title**: 空文字不可、255文字以内
3. **isbn**: NULL または 10文字（ISBN-10）または 13文字（ISBN-13）
4. **status**: 'available', 'borrowed', 'reserved' のいずれか
5. **published_year**: NULL または 1000-9999 の範囲
