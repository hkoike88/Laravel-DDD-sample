# Data Model: 職員アカウント編集機能

**Date**: 2026-01-06
**Feature**: EPIC-004 職員アカウント編集機能

## Entities

### Staff（職員）- 既存エンティティ

職員アカウントを表すエンティティ。EPIC-003で作成済み。

| Attribute | Type | Required | Description |
|-----------|------|----------|-------------|
| id | ULID (string) | Yes | 一意識別子 |
| name | string(100) | Yes | 氏名 |
| email | string(255) | Yes | メールアドレス（一意） |
| password | string(255) | Yes | ハッシュ化されたパスワード |
| is_admin | boolean | Yes | 管理者フラグ |
| is_locked | boolean | Yes | ロック状態 |
| failed_login_attempts | integer | Yes | ログイン失敗回数 |
| locked_at | datetime | No | ロック日時 |
| created_at | datetime | Yes | 作成日時 |
| updated_at | datetime | Yes | 更新日時（楽観的ロックに使用） |

### AuditLog（監査ログ）- 既存エンティティ

操作の記録を表す。ログファイルベースで永続化。

| Attribute | Type | Required | Description |
|-----------|------|----------|-------------|
| operator_id | ULID (string) | Yes | 操作者の職員ID |
| target_staff_id | ULID (string) | Yes | 対象の職員ID |
| action | string | Yes | 操作種別（created/updated/password_reset） |
| changes | json | No | 変更内容（before/after） |
| timestamp | datetime | Yes | 操作日時 |

## Validation Rules

### 職員更新時

| Field | Rule | Error Message |
|-------|------|---------------|
| name | 必須、1-100文字 | 氏名は必須です / 氏名は100文字以内で入力してください |
| email | 必須、メール形式、一意（自分以外） | メールアドレスは必須です / 有効なメールアドレスを入力してください / このメールアドレスは既に使用されています |
| role | 必須、admin または staff | 権限は必須です / 無効な権限です |
| updated_at | 必須、ISO 8601形式 | 更新日時は必須です |

### ビジネスルール検証

| Rule ID | Rule | Error Response |
|---------|------|----------------|
| BR-01 | 自己権限変更の防止 | 422: 自分自身の権限は変更できません |
| BR-02 | 最後の管理者保護 | 422: 最後の管理者アカウントの権限は変更できません |
| BR-03 | 楽観的ロック | 409: 他のユーザーによって更新されています |
| BR-04 | メールアドレス一意性 | 422: このメールアドレスは既に使用されています |

## State Transitions

### 職員権限の状態遷移

```
[一般職員] <---> [管理者]

制約:
- 管理者 → 一般職員: 管理者が2人以上の場合のみ可能
- 自分自身の権限変更は不可
```

## Relationships

```
Staff (1) --- (N) AuditLog
  ↑                  ↑
  |                  |
操作対象          operator_id で操作者を参照
```

## DTOs

### UpdateStaffInput

```
{
  name: string          // 氏名
  email: string         // メールアドレス
  role: "admin" | "staff"  // 権限
  updatedAt: string     // 更新日時（ISO 8601）- 楽観的ロック用
}
```

### UpdateStaffOutput

```
{
  id: string            // 職員ID
  name: string          // 氏名
  email: string         // メールアドレス
  role: "admin" | "staff"  // 権限
  updatedAt: string     // 更新後の更新日時
}
```

### ResetPasswordOutput

```
{
  temporaryPassword: string  // 一時パスワード（平文）
}
```

### StaffDetailOutput

```
{
  id: string            // 職員ID
  name: string          // 氏名
  email: string         // メールアドレス
  role: "admin" | "staff"  // 権限
  isCurrentUser: boolean   // ログイン中のユーザーかどうか
  updatedAt: string     // 更新日時（楽観的ロック用）
  createdAt: string     // 作成日時
}
```

## Database Schema

既存の `staffs` テーブルを使用。変更なし。

```sql
-- 既存テーブル（参照用）
CREATE TABLE staffs (
    id VARCHAR(26) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    is_locked BOOLEAN NOT NULL DEFAULT FALSE,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_staffs_email ON staffs(email);
CREATE INDEX idx_staffs_is_admin ON staffs(is_admin);
```

## Repository Interface Extensions

```php
interface StaffRepositoryInterface
{
    // 既存メソッド...

    /**
     * 管理者の人数をカウント
     * @return int 管理者数
     */
    public function countAdmins(): int;
}
```
