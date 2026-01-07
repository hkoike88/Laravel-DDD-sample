# Data Model: 蔵書登録API実装

**Date**: 2025-12-24
**Feature**: 001-book-registration-api

## Entities

### Book（蔵書）- 既存エンティティ

**Location**: `backend/packages/Domain/Book/Domain/Model/Book.php`

| フィールド | 型 | 必須 | 説明 | バリデーション |
|-----------|-----|------|------|---------------|
| id | BookId (ULID) | ✅ | 蔵書ID | システム自動生成 |
| title | string | ✅ | 書籍タイトル | 1-500文字、空白のみ不可 |
| author | string \| null | - | 著者名 | 最大200文字 |
| isbn | ISBN \| null | - | 国際標準図書番号 | ISBN-10/13形式 |
| publisher | string \| null | - | 出版社名 | 最大200文字 |
| publishedYear | int \| null | - | 出版年 | 1〜現在年+5 |
| genre | string \| null | - | ジャンル | 最大100文字 |
| status | BookStatus | ✅ | 貸出状態 | 登録時は"available"固定 |

**ファクトリメソッド**:
```php
Book::create(
    id: BookId,
    title: string,
    author: ?string = null,
    isbn: ?ISBN = null,
    publisher: ?string = null,
    publishedYear: ?int = null,
    genre: ?string = null,
): Book
```

**ドメインルール**:
- タイトルが空または空白のみの場合 → `EmptyBookTitleException`
- 新規作成時のstatusは必ず`available`

## Value Objects

### BookId - 既存

**Location**: `backend/packages/Domain/Book/Domain/ValueObjects/BookId.php`

| 属性 | 型 | 説明 |
|------|-----|------|
| value | string | ULID形式（26文字） |

**生成**: `BookId::generate()` - symfony/uidでULID生成

### ISBN - 既存

**Location**: `backend/packages/Domain/Book/Domain/ValueObjects/ISBN.php`

| 属性 | 型 | 説明 |
|------|-----|------|
| value | string | 正規化されたISBN（ハイフンなし） |

**バリデーション**:
- ISBN-10: 10桁（最後はXまたは数字）
- ISBN-13: 13桁（978/979で始まる）
- チェックディジット検証

**例外**: `InvalidISBNException`

### BookStatus - 既存

**Location**: `backend/packages/Domain/Book/Domain/ValueObjects/BookStatus.php`

| 値 | 説明 |
|----|------|
| available | 利用可能 |
| borrowed | 貸出中 |
| reserved | 予約中 |

**本機能での使用**: 登録時は`BookStatus::available()`固定

## DTOs

### CreateBookCommand - 新規作成

**Location**: `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/CreateBookCommand.php`

```php
final readonly class CreateBookCommand
{
    public function __construct(
        public string $title,
        public ?string $author = null,
        public ?string $isbn = null,
        public ?string $publisher = null,
        public ?int $publishedYear = null,
        public ?string $genre = null,
    ) {}
}
```

## State Transitions

```
[登録リクエスト]
    ↓
[バリデーション] → 失敗 → [422 エラーレスポンス]
    ↓ 成功
[Book::create()] → EmptyBookTitleException/InvalidISBNException → [422 エラーレスポンス]
    ↓ 成功
[BookRepository::save()]
    ↓
[201 Created + BookResource]
```

## Database Schema

### books テーブル - 既存

```sql
CREATE TABLE books (
    id CHAR(26) PRIMARY KEY,           -- ULID
    title VARCHAR(500) NOT NULL,
    author VARCHAR(200),
    isbn VARCHAR(13),                   -- ハイフンなし正規化済み
    publisher VARCHAR(200),
    published_year INT,
    genre VARCHAR(100),
    status ENUM('available', 'borrowed', 'reserved') DEFAULT 'available',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_isbn (isbn)
);
```

## Relationships

```
Book (1) ─── (1) BookId
Book (0..1) ─── (0..1) ISBN
Book (1) ─── (1) BookStatus
```

本機能ではBook単体の登録のみ。貸出・予約との関連は別機能で実装。
