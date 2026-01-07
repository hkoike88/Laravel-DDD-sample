# Data Model: アカウントロック機能

**Branch**: `006-account-lock` | **Date**: 2025-12-26

## 概要

アカウントロック機能に関連するエンティティとデータ構造を定義する。

---

## エンティティ

### Staff（職員）

ログイン認証の対象となる職員エンティティ。アカウントロック状態を管理する。

#### 属性

| 属性 | 型 | 制約 | 説明 |
|------|-----|------|------|
| id | StaffId (ULID) | 主キー、26文字 | 職員ID |
| email | Email | ユニーク、最大255文字 | メールアドレス |
| password | Password | 必須 | ハッシュ化済みパスワード |
| name | StaffName | 必須、最大100文字 | 職員名 |
| isLocked | boolean | デフォルト: false | ロック状態 |
| failedLoginAttempts | int | デフォルト: 0、0以上 | 連続ログイン失敗回数 |
| lockedAt | DateTimeImmutable | nullable | ロック日時 |
| createdAt | DateTimeImmutable | 自動設定 | 作成日時 |
| updatedAt | DateTimeImmutable | 自動設定 | 更新日時 |

#### 状態遷移

```
[未ロック] ──(5回連続失敗)──→ [ロック済み]
    ↑                              │
    └──────(管理者解除: Phase 2)───┘
```

#### ビジネスルール

| ルール | 説明 |
|--------|------|
| BR-001 | ログイン失敗時、failedLoginAttempts を +1 する |
| BR-002 | failedLoginAttempts が 5 に達したらアカウントをロックする |
| BR-003 | ログイン成功時、failedLoginAttempts を 0 にリセットする |
| BR-004 | ロック中のアカウントはパスワード検証前にロックエラーを返す |
| BR-005 | ロック時、lockedAt に現在日時を記録する |

#### メソッド

| メソッド | 引数 | 戻り値 | 説明 |
|---------|------|--------|------|
| lock() | なし | void | アカウントをロックし、lockedAt を設定 |
| unlock() | なし | void | ロック解除し、失敗回数もリセット |
| incrementFailedLoginAttempts() | なし | void | 失敗回数を +1 |
| resetFailedLoginAttempts() | なし | void | 失敗回数を 0 にリセット |
| isLocked() | なし | bool | ロック状態を返す |
| failedLoginAttempts() | なし | int | 失敗回数を返す |
| lockedAt() | なし | DateTimeImmutable? | ロック日時を返す |

---

## 例外

### AccountLockedException

アカウントがロックされている場合にスローされる例外。

| 属性 | 型 | 説明 |
|------|-----|------|
| retryAfterSeconds | int | リトライ可能までの秒数（1800秒 = 30分）|

---

## データベーススキーマ

### staffs テーブル（既存）

```sql
CREATE TABLE staffs (
    id CHAR(26) PRIMARY KEY COMMENT '職員ID（ULID）',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'メールアドレス',
    password VARCHAR(255) NOT NULL COMMENT 'ハッシュ化済みパスワード',
    name VARCHAR(100) NOT NULL COMMENT '職員名',
    is_locked BOOLEAN DEFAULT FALSE COMMENT 'ロック状態',
    failed_login_attempts INT UNSIGNED DEFAULT 0 COMMENT 'ログイン失敗回数',
    locked_at TIMESTAMP NULL COMMENT 'ロック日時',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## API レスポンス型

### エラーレスポンス（ロック時）

```typescript
// HTTP 423 Locked
{
  "message": "アカウントがロックされています。管理者にお問い合わせください"
}
```

### エラーレスポンス（認証失敗時）

```typescript
// HTTP 401 Unauthorized
{
  "message": "メールアドレスまたはパスワードが正しくありません"
}
```
