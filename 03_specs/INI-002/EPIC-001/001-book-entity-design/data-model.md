# Data Model: 蔵書エンティティ・Value Object 設計

**Date**: 2025-12-24
**Feature**: 001-book-entity-design

---

## エンティティ

### Book（蔵書）

図書館が所蔵する書籍を表すエンティティ。集約ルート。

| 属性 | 型 | 必須 | 説明 |
|------|-----|------|------|
| id | BookId | ✓ | 蔵書の一意識別子（ULID） |
| title | string | ✓ | 書籍タイトル（空白不可） |
| author | string | - | 著者名（複数著者は「,」区切り） |
| isbn | ISBN | - | 国際標準図書番号（ISBN-10 または ISBN-13） |
| publisher | string | - | 出版社名 |
| publishedYear | int | - | 出版年（1000-9999） |
| genre | string | - | ジャンル（自由入力） |
| status | BookStatus | ✓ | 貸出状態（available/borrowed/reserved） |

#### ビジネスルール

1. **タイトル必須**: タイトルは空白であってはならない
2. **初期状態**: 新規作成時のステータスは `available`
3. **状態遷移制約**: BookStatus の遷移ルールに従う
4. **不変性**: id は作成後に変更不可

#### メソッド

| メソッド | 説明 | 事前条件 | 事後条件 |
|---------|------|----------|----------|
| `create(...)` | ファクトリメソッド | 有効なパラメータ | 新規 Book インスタンス |
| `borrow()` | 貸出処理 | status = available | status = borrowed |
| `return()` | 返却処理 | status = borrowed | status = available |
| `reserve()` | 予約処理 | status = available | status = reserved |
| `lendToReserver()` | 予約者への貸出 | status = reserved | status = borrowed |
| `cancelReservation()` | 予約キャンセル | status = reserved | status = available |
| `isAvailable()` | 利用可能判定 | - | boolean |

---

## Value Objects

### BookId（蔵書ID）

蔵書を一意に識別する識別子。ULID 形式。

| 属性 | 型 | 説明 |
|------|-----|------|
| value | string | 26文字の ULID 文字列 |

#### バリデーションルール

- 26文字固定長
- Crockford's Base32 文字セット（0-9, A-Z から I, L, O, U を除く）

#### メソッド

| メソッド | 説明 |
|---------|------|
| `generate()` | 新規 ULID を生成 |
| `fromString(string $value)` | 文字列から生成 |
| `value()` | 内部値を取得 |
| `equals(BookId $other)` | 等価性判定 |

---

### ISBN（国際標準図書番号）

書籍を国際的に識別するための番号。ISBN-10 または ISBN-13 形式をサポート。

| 属性 | 型 | 説明 |
|------|-----|------|
| value | string | ハイフンなしの正規化された ISBN |
| type | string | "ISBN-10" または "ISBN-13" |

#### バリデーションルール

**ISBN-13:**
- 13桁の数字
- 先頭3桁は 978 または 979
- チェックディジット検証（Modulus 10, Weight 1 and 3）

**ISBN-10:**
- 10桁（最後の桁は数字または 'X'）
- チェックディジット検証（Modulus 11, Weight 10-2）

#### メソッド

| メソッド | 説明 |
|---------|------|
| `fromString(string $value)` | 文字列から生成（ハイフンあり/なし両対応） |
| `value()` | 正規化された値（ハイフンなし） |
| `formatted()` | ハイフン付きフォーマット |
| `isISBN13()` | ISBN-13 判定 |
| `isISBN10()` | ISBN-10 判定 |
| `equals(ISBN $other)` | 等価性判定 |

---

### BookStatus（蔵書ステータス）

蔵書の現在の貸出状態を表す列挙型的 Value Object。

| 状態 | 値 | 説明 |
|------|-----|------|
| Available | "available" | 利用可能（貸出・予約可能） |
| Borrowed | "borrowed" | 貸出中（返却のみ可能） |
| Reserved | "reserved" | 予約中（予約者への貸出またはキャンセル可能） |

#### 状態遷移図

```
                    ┌─────────────────┐
                    │                 │
         reserve()  │   ┌─────────┐   │  cancelReservation()
        ┌───────────┼──>│ Reserved│───┼──────────────────┐
        │           │   └────┬────┘   │                  │
        │           │        │        │                  │
        │           │        │lendToReserver()           │
        │           │        │        │                  │
        │           │        ▼        │                  │
   ┌────┴────┐      │   ┌─────────┐   │                  │
   │Available│<─────┼───│ Borrowed│   │                  │
   └─────────┘      │   └─────────┘   │                  │
        ▲           │        │        │                  │
        │           │        │return()│                  │
        │           │        │        │                  │
        └───────────┴────────┴────────┴──────────────────┘
                    borrow()
```

#### メソッド

| メソッド | 説明 |
|---------|------|
| `available()` | Available 状態を生成 |
| `borrowed()` | Borrowed 状態を生成 |
| `reserved()` | Reserved 状態を生成 |
| `from(string $value)` | 文字列から生成 |
| `value()` | 内部値を取得 |
| `canBorrow()` | 貸出可能か判定 |
| `canReturn()` | 返却可能か判定 |
| `canReserve()` | 予約可能か判定 |
| `canLendToReserver()` | 予約者への貸出可能か判定 |
| `canCancelReservation()` | 予約キャンセル可能か判定 |
| `isAvailable()` | Available 状態か判定 |
| `isBorrowed()` | Borrowed 状態か判定 |
| `isReserved()` | Reserved 状態か判定 |
| `equals(BookStatus $other)` | 等価性判定 |

---

## 例外クラス

### InvalidISBNException

ISBN の形式またはチェックディジットが不正な場合にスローされる。

| 属性 | 型 | 説明 |
|------|-----|------|
| invalidValue | string | 不正な入力値 |
| reason | string | エラー理由（"invalid_format", "invalid_checksum"） |

---

### InvalidBookStatusTransitionException

不正な状態遷移が試みられた場合にスローされる。

| 属性 | 型 | 説明 |
|------|-----|------|
| from | BookStatus | 遷移元の状態 |
| to | BookStatus | 遷移先の状態 |
| action | string | 試みられた操作名 |

---

### EmptyBookTitleException

タイトルが空白の場合にスローされる。

| 属性 | 型 | 説明 |
|------|-----|------|
| message | string | "蔵書タイトルは必須です" |

---

## リポジトリインターフェース

### BookRepositoryInterface

蔵書の永続化と取得を抽象化するインターフェース。

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `find(BookId $id)` | Book | ID で蔵書を取得（存在しない場合は例外） |
| `findByIsbn(ISBN $isbn)` | list\<Book\> | ISBN で蔵書を検索（複本対応） |
| `save(Book $book)` | void | 蔵書を保存（新規作成または更新） |
| `delete(BookId $id)` | void | 蔵書を削除 |

---

## データベーススキーマ（参考）

本フィーチャーではドメイン層のみを実装し、データベーススキーマは後続タスクで定義する。
参考として想定されるスキーマを示す。

```sql
CREATE TABLE books (
    id CHAR(26) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NULL,
    isbn CHAR(13) NULL,
    publisher VARCHAR(255) NULL,
    published_year SMALLINT UNSIGNED NULL,
    genre VARCHAR(100) NULL,
    status ENUM('available', 'borrowed', 'reserved') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_isbn (isbn),
    INDEX idx_status (status),
    INDEX idx_title (title(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 設計判断メモ

1. **ISBN の正規化**: 内部的にはハイフンなしで保持し、表示時にフォーマットする
2. **著者の扱い**: 現段階では単一文字列。将来的に Author エンティティへの分離を検討
3. **ジャンルの扱い**: 現段階では自由入力。将来的に Genre マスタへの移行を検討
4. **複本対応**: 同一 ISBN で複数の Book エンティティを許容（それぞれ異なる BookId）
5. **出版年の範囲**: 1000-9999 年を許容（古文書から未来の予約出版まで対応）
