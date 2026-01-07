# Data Model: 蔵書検索API

**Feature**: 003-book-search-api
**Date**: 2025-12-24

## エンティティ概要

本フィーチャーは既存の `Book` エンティティを使用する読み取り専用機能。新規エンティティの追加は不要。

---

## 1. Book（蔵書）- 既存

図書館が所蔵する書籍を表すエンティティ（集約ルート）。

### 属性

| 属性 | 型 | 必須 | 説明 |
|------|-----|------|------|
| id | BookId (ULID) | Yes | 蔵書の一意識別子 |
| title | string | Yes | 書籍タイトル |
| author | string | No | 著者名 |
| isbn | ISBN | No | ISBN（ISBN-10またはISBN-13） |
| publisher | string | No | 出版社名 |
| publishedYear | int | No | 出版年 |
| genre | string | No | ジャンル |
| status | BookStatus | Yes | 貸出状態（available/borrowed/reserved） |

### 検索対象フィールド

| フィールド | 検索方式 | 仕様要件 |
|-----------|----------|----------|
| title | 部分一致（LIKE %keyword%） | FR-001 |
| author | 部分一致（LIKE %keyword%） | FR-002 |
| isbn | 完全一致 | FR-003 |

### ソート対象フィールド

| フィールド | デフォルト | 方向 |
|-----------|-----------|------|
| title | Yes（昇順） | asc |

※ソート順の変更機能はスコープ外

---

## 2. BookSearchCriteria（検索条件）- 既存 + 拡張

検索パラメータを保持する不変オブジェクト（DTO）。

### 現在の属性

| 属性 | 型 | デフォルト | 説明 |
|------|-----|-----------|------|
| title | string? | null | タイトル検索キーワード |
| author | string? | null | 著者名検索キーワード |
| publisher | string? | null | 出版社検索キーワード |
| genre | string? | null | ジャンル（完全一致） |
| status | BookStatus? | null | 貸出状態 |
| publishedYearFrom | int? | null | 出版年（From） |
| publishedYearTo | int? | null | 出版年（To） |
| page | int | 1 | ページ番号 |
| pageSize | int | 20 | ページサイズ |
| sortField | string | 'title' | ソートフィールド |
| sortDirection | string | 'asc' | ソート方向 |

### 追加が必要な属性

| 属性 | 型 | デフォルト | 説明 | 仕様要件 |
|------|-----|-----------|------|----------|
| isbn | string? | null | ISBN検索キーワード（完全一致） | FR-003 |

### バリデーションルール

| 属性 | ルール |
|------|--------|
| page | >= 1 |
| pageSize | 1 <= x <= 100 |
| isbn | ISBN-10形式（10桁）またはISBN-13形式（13桁） |

---

## 3. BookCollection（検索結果）- 既存

検索結果の蔵書リストとページネーション情報を保持。

### 属性

| 属性 | 型 | 説明 |
|------|-----|------|
| items | list<Book> | 蔵書リスト |
| totalCount | int | 総件数 |
| currentPage | int | 現在のページ番号 |
| totalPages | int | 総ページ数 |
| pageSize | int | ページサイズ |

### メソッド

| メソッド | 戻り値 | 説明 |
|---------|--------|------|
| isEmpty() | bool | コレクションが空か判定 |
| hasNextPage() | bool | 次のページが存在するか |
| hasPreviousPage() | bool | 前のページが存在するか |
| count() | int | 現在ページの蔵書件数 |

---

## 4. 値オブジェクト - 既存

### BookId

- ULID形式の識別子
- 不変オブジェクト

### ISBN

- ISBN-10（10桁）またはISBN-13（13桁）
- チェックディジット検証あり
- 不変オブジェクト

### BookStatus

- 列挙型: `available`, `borrowed`, `reserved`
- 状態遷移ルールを内包

---

## データベーススキーマ

### books テーブル - 既存

```sql
CREATE TABLE books (
    id VARCHAR(26) PRIMARY KEY,           -- ULID
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NULL,
    isbn VARCHAR(13) NULL,                -- ISBN-10 or ISBN-13
    publisher VARCHAR(255) NULL,
    published_year INT NULL,
    genre VARCHAR(100) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_books_title (title(100)),   -- 部分一致検索用
    INDEX idx_books_author (author(100)), -- 部分一致検索用
    INDEX idx_books_isbn (isbn)           -- 完全一致検索用
);
```

---

## 関連図

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
├─────────────────────────────────────────────────────────┤
│  SearchBooksRequest  ──────►  BookController            │
│         │                           │                    │
│         ▼                           ▼                    │
│  [HTTP Validation]           BookResource               │
│                              BookCollectionResource     │
└─────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                    Application Layer                     │
├─────────────────────────────────────────────────────────┤
│  SearchBooksQuery  ──────►  SearchBooksHandler          │
│         │                           │                    │
│         ▼                           ▼                    │
│  BookSearchCriteria           BookCollection            │
└─────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                      Domain Layer                        │
├─────────────────────────────────────────────────────────┤
│  BookRepositoryInterface.search(criteria)               │
│         │                                                │
│         ▼                                                │
│  Book (Entity)  ◄───  BookId, ISBN, BookStatus (VO)    │
└─────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                  Infrastructure Layer                    │
├─────────────────────────────────────────────────────────┤
│  EloquentBookRepository  ──────►  BookRecord            │
│         │                              │                 │
│         ▼                              ▼                 │
│  [Query Builder]                 books table            │
└─────────────────────────────────────────────────────────┘
```

---

## 変更サマリー

| コンポーネント | 変更種別 | 内容 |
|---------------|---------|------|
| BookSearchCriteria | 拡張 | isbn属性を追加 |
| EloquentBookRepository | 拡張 | ISBN検索条件の適用ロジック追加 |
| SearchBooksQuery | 新規 | UseCase入力DTO |
| SearchBooksHandler | 新規 | UseCase処理 |
| BookController | 新規 | HTTPエンドポイント |
| SearchBooksRequest | 新規 | HTTPリクエストバリデーション |
| BookResource | 新規 | JSONレスポンス変換 |
| BookCollectionResource | 新規 | ページネーション付きレスポンス |
