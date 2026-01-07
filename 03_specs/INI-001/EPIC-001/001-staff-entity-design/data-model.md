# Data Model: 職員エンティティの設計

**Branch**: `001-staff-entity-design` | **Date**: 2025-12-25

## Entity: Staff（職員）

### 概要

システムにログインして操作を行う人物を表すエンティティ（集約ルート）。認証機能の基盤となる職員データを管理する。

### 属性

| 属性名 | 型 | 必須 | 説明 |
|--------|-----|------|------|
| id | StaffId | ✓ | 職員を一意に識別する ULID（26文字） |
| email | Email | ✓ | ログイン識別子として使用されるメールアドレス |
| password | Password | ✓ | ハッシュ化されたパスワード |
| name | StaffName | ✓ | 職員の表示名 |
| isLocked | bool | ✓ | アカウントロック状態（デフォルト: false） |
| failedLoginAttempts | int | ✓ | ログイン失敗回数（デフォルト: 0） |
| lockedAt | DateTime | - | ロック日時（null 許容） |
| createdAt | DateTime | ✓ | 作成日時 |
| updatedAt | DateTime | ✓ | 更新日時 |

### ビジネスルール

1. **メールアドレスの一意性**: 同一メールアドレスを持つ職員は存在できない
2. **パスワードの不可逆性**: パスワードはハッシュ化後に保存され、平文での取得は不可
3. **ロック状態の遷移**:
   - ロック時: `isLocked = true`, `lockedAt = 現在日時`
   - アンロック時: `isLocked = false`, `lockedAt = null`, `failedLoginAttempts = 0`
4. **失敗回数の管理**: ログイン失敗ごとにインクリメント、成功時またはアンロック時にリセット

### メソッド

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `create(...)` | Staff | 新規職員を作成（ファクトリメソッド） |
| `reconstruct(...)` | Staff | 永続化データから復元 |
| `lock()` | void | アカウントをロック |
| `unlock()` | void | アカウントをアンロック |
| `incrementFailedLoginAttempts()` | void | ログイン失敗回数を増加 |
| `resetFailedLoginAttempts()` | void | ログイン失敗回数をリセット |
| `isLocked()` | bool | ロック状態を取得 |
| `verifyPassword(string $plainPassword)` | bool | パスワード検証 |

---

## Value Object: StaffId（職員ID）

### 概要

職員を一意に識別する 26 文字の ULID 値。タイムスタンプ順にソート可能。

### 属性

| 属性名 | 型 | 制約 |
|--------|-----|------|
| value | string | 26文字固定、Crockford's Base32 エンコーディング |

### バリデーション

- 空文字不可
- 26文字固定長（ULID 形式）

### メソッド

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `generate()` | StaffId | 新規 ULID を生成 |
| `fromString(string $value)` | StaffId | 文字列から生成 |
| `value()` | string | 内部値を取得 |
| `equals(StaffId $other)` | bool | 等価性判定 |
| `__toString()` | string | 文字列変換 |

---

## Value Object: Email（メールアドレス）

### 概要

職員の連絡先かつログイン識別子として使用されるメールアドレス。

### 属性

| 属性名 | 型 | 制約 |
|--------|-----|------|
| value | string | RFC 5322 準拠、小文字正規化、最大 255 文字 |

### バリデーション

- 空文字不可
- 有効なメールアドレス形式（`filter_var` + `FILTER_VALIDATE_EMAIL`）
- 255 文字以内

### 正規化

- 入力時に小文字に変換（`mb_strtolower`）

### メソッド

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `create(string $value)` | Email | メールアドレスを検証して生成 |
| `value()` | string | 内部値を取得 |
| `equals(Email $other)` | bool | 等価性判定 |
| `__toString()` | string | 文字列変換 |

### 例外

- `InvalidEmailException`: 形式不正時

---

## Value Object: Password（パスワード）

### 概要

認証に使用されるハッシュ化された秘密情報。

### 属性

| 属性名 | 型 | 制約 |
|--------|-----|------|
| hashedValue | string | bcrypt ハッシュ文字列（60文字） |

### バリデーション（平文入力時）

- 空文字不可
- 8 文字以上 72 文字以下

### メソッド

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `fromPlainText(string $plainText)` | Password | 平文からハッシュ化して生成 |
| `fromHash(string $hash)` | Password | ハッシュ値から復元 |
| `verify(string $plainText)` | bool | 平文パスワードを検証 |
| `hashedValue()` | string | ハッシュ値を取得 |

### 例外

- `InvalidPasswordException`: 長さ制約違反時

### 設計メモ

- Domain 層の Password 値オブジェクトは「ハッシュ化済み」の状態を保持
- ハッシュ化処理自体は Application/Infrastructure 層のサービスで実行
- `fromPlainText` は内部でハッシュ化サービスを呼び出すファクトリメソッド

---

## Value Object: StaffName（職員名）

### 概要

職員の表示名。

### 属性

| 属性名 | 型 | 制約 |
|--------|-----|------|
| value | string | 最大 100 文字 |

### バリデーション

- 空文字・空白のみ不可（トリム後）
- 100 文字以内
- 制御文字は除去
- 絵文字は許可

### メソッド

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `create(string $value)` | StaffName | 職員名を検証して生成 |
| `value()` | string | 内部値を取得 |
| `equals(StaffName $other)` | bool | 等価性判定 |
| `__toString()` | string | 文字列変換 |

### 例外

- 空文字時はドメイン例外をスロー（具体的な例外クラスは実装時に決定）

---

## Database Schema

### staffs テーブル

```sql
CREATE TABLE staffs (
    id CHAR(26) NOT NULL COMMENT '職員ID（ULID）',
    email VARCHAR(255) NOT NULL COMMENT 'メールアドレス（小文字正規化済み）',
    password VARCHAR(255) NOT NULL COMMENT 'ハッシュ化済みパスワード',
    name VARCHAR(100) NOT NULL COMMENT '職員名',
    is_locked BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'ロック状態',
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ログイン失敗回数',
    locked_at TIMESTAMP NULL COMMENT 'ロック日時',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',

    PRIMARY KEY (id),
    UNIQUE KEY idx_staffs_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='職員';
```

### インデックス

| インデックス名 | カラム | 種類 | 用途 |
|---------------|--------|------|------|
| PRIMARY | id | PRIMARY KEY | ID 検索 |
| idx_staffs_email | email | UNIQUE | メール検索・重複チェック |

---

## Repository Interface

### StaffRepositoryInterface

```
interface StaffRepositoryInterface {
    find(StaffId $id): Staff
    findOrNull(StaffId $id): ?Staff
    findByEmail(Email $email): ?Staff
    existsByEmail(Email $email): bool
    save(Staff $staff): void
    delete(StaffId $id): void
}
```

| メソッド | 説明 |
|---------|------|
| `find` | ID で検索（見つからない場合は StaffNotFoundException） |
| `findOrNull` | ID で検索（見つからない場合は null） |
| `findByEmail` | メールアドレスで検索 |
| `existsByEmail` | メールアドレスの存在確認（重複チェック用） |
| `save` | 保存（新規・更新） |
| `delete` | 削除（冪等） |

---

## 関連図

```
┌─────────────────────────────────────────────┐
│                   Staff                      │
│  (Aggregate Root)                           │
├─────────────────────────────────────────────┤
│  - id: StaffId                              │
│  - email: Email                             │
│  - password: Password                       │
│  - name: StaffName                          │
│  - isLocked: bool                           │
│  - failedLoginAttempts: int                 │
│  - lockedAt: ?DateTime                      │
│  - createdAt: DateTime                      │
│  - updatedAt: DateTime                      │
├─────────────────────────────────────────────┤
│  + create()                                 │
│  + reconstruct()                            │
│  + lock()                                   │
│  + unlock()                                 │
│  + incrementFailedLoginAttempts()           │
│  + resetFailedLoginAttempts()               │
│  + verifyPassword()                         │
└─────────────────────────────────────────────┘
         │              │              │              │
         ▼              ▼              ▼              ▼
    ┌─────────┐   ┌─────────┐   ┌──────────┐   ┌───────────┐
    │ StaffId │   │  Email  │   │ Password │   │ StaffName │
    │  (VO)   │   │  (VO)   │   │   (VO)   │   │   (VO)    │
    └─────────┘   └─────────┘   └──────────┘   └───────────┘
```
