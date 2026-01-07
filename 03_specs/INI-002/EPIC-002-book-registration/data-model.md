# Data Model: 蔵書登録機能

**Feature Branch**: `001-book-registration`
**Date**: 2026-01-06

## エンティティ定義

### Book（蔵書）

**概要**: 図書館が所蔵する書籍を表すエンティティ（集約ルート）

**属性**:

| 属性 | 型 | 必須 | 説明 | バリデーション |
|------|-----|------|------|--------------|
| id | BookId (ULID) | Yes | 蔵書ID | 26文字固定、自動生成 |
| title | string | Yes | 書籍タイトル | 1〜200文字 |
| author | string | No | 著者名 | 最大100文字 |
| isbn | ISBN | No | ISBN | ISBN-10 または ISBN-13 形式 |
| publisher | string | No | 出版社名 | 最大100文字 |
| publishedYear | int | No | 出版年 | 1000〜現在年+1 |
| genre | string | No | ジャンル | 最大100文字 |
| status | BookStatus | Yes | 貸出状態 | available/borrowed/reserved |
| registeredBy | StaffId (ULID) | Yes | 登録者ID | **新規追加** |
| registeredAt | datetime | Yes | 登録日時 | **新規追加** |
| createdAt | datetime | Yes | 作成日時 | 自動設定 |
| updatedAt | datetime | Yes | 更新日時 | 自動更新 |

**Value Objects**:

- **BookId**: ULID形式の蔵書識別子
- **ISBN**: ISBN-10 または ISBN-13 を正規化して保持
- **BookStatus**: 貸出状態（available, borrowed, reserved）
- **StaffId**: ULID形式の職員識別子

### Staff（職員）- 既存参照

**概要**: 蔵書登録を行う図書館職員

**関係**: Book.registeredBy → Staff.id（多対一）

## データベーススキーマ

### books テーブル（拡張）

```sql
-- 既存カラム
id VARCHAR(26) PRIMARY KEY,
title VARCHAR(255) NOT NULL,
author VARCHAR(255) NULL,
isbn VARCHAR(13) NULL,
publisher VARCHAR(255) NULL,
published_year SMALLINT UNSIGNED NULL,
genre VARCHAR(100) NULL,
status VARCHAR(20) NOT NULL DEFAULT 'available',
created_at TIMESTAMP NULL,
updated_at TIMESTAMP NULL,

-- 新規追加カラム
registered_by VARCHAR(26) NULL,
registered_at TIMESTAMP NULL,

-- 外部キー（optional: 参照整合性）
CONSTRAINT fk_books_registered_by
  FOREIGN KEY (registered_by) REFERENCES staffs(id) ON DELETE SET NULL
```

### マイグレーション

```php
// 2026_01_06_000000_add_registration_columns_to_books_table.php
Schema::table('books', function (Blueprint $table) {
    $table->string('registered_by', 26)->nullable()->after('status');
    $table->timestamp('registered_at')->nullable()->after('registered_by');

    // 外部キー制約
    $table->foreign('registered_by')
          ->references('id')
          ->on('staffs')
          ->onDelete('set null');
});
```

## 状態遷移

### Book.status

```
                    ┌─────────────┐
                    │  available  │
                    └──────┬──────┘
                           │
          ┌────────────────┼────────────────┐
          │ borrow()       │ reserve()      │
          ▼                ▼                │
    ┌──────────┐     ┌──────────┐          │
    │ borrowed │     │ reserved │──────────┘
    └────┬─────┘     └────┬─────┘ cancelReservation()
         │                │
         │ return()       │ lendToReserver()
         │                │
         └────────────────┘
                │
                ▼
          ┌──────────┐
          │ available │
          └──────────┘
```

**Note**: 蔵書登録時の初期状態は常に `available`

## リレーション

```
┌─────────────────┐       ┌─────────────────┐
│     Staff       │       │      Book       │
├─────────────────┤       ├─────────────────┤
│ id (PK, ULID)   │◄──────│ registered_by   │
│ name            │  1:N  │ id (PK, ULID)   │
│ email           │       │ title           │
│ ...             │       │ ...             │
└─────────────────┘       └─────────────────┘
```

## バリデーションルール

### フロントエンド（Zod スキーマ）

```typescript
const createBookSchema = z.object({
  title: z.string()
    .min(1, 'タイトルは必須です')
    .max(200, 'タイトルは200文字以内で入力してください'),
  author: z.string()
    .max(100, '著者名は100文字以内で入力してください')
    .optional()
    .nullable(),
  isbn: z.string()
    .regex(/^(97[89]\d{10}|\d{9}[\dX])$/, 'ISBNの形式が正しくありません')
    .optional()
    .nullable()
    .or(z.literal('')),
  publisher: z.string()
    .max(100, '出版社名は100文字以内で入力してください')
    .optional()
    .nullable(),
  published_year: z.number()
    .int()
    .min(1000, '出版年は1000以上で入力してください')
    .max(new Date().getFullYear() + 1)
    .optional()
    .nullable(),
  genre: z.string()
    .max(100, 'ジャンルは100文字以内で入力してください')
    .optional()
    .nullable(),
})
```

### バックエンド（Laravel FormRequest）

```php
return [
    'title' => ['required', 'string', 'min:1', 'max:200'],
    'author' => ['nullable', 'string', 'max:100'],
    'isbn' => ['nullable', 'string', 'regex:/^(97[89]\d{10}|\d{9}[\dX])$/'],
    'publisher' => ['nullable', 'string', 'max:100'],
    'published_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
    'genre' => ['nullable', 'string', 'max:100'],
];
```

## インデックス

既存インデックス（変更なし）:
- `PRIMARY KEY (id)`
- `INDEX (isbn)` - ISBN重複チェックで使用
- `INDEX (status)`
- `INDEX (genre)`
- `INDEX (published_year)`
- `INDEX (title(100))` - タイトル検索
- `INDEX (author(100))` - 著者検索

追加インデックス:
- `INDEX (registered_by)` - 登録者での検索用
