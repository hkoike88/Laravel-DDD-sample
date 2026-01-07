# Data Model: セキュリティ対策準備

**Feature**: 001-security-preparation
**Date**: 2026-01-06

---

## エンティティ一覧

| エンティティ | 説明 | 新規/既存 |
|-------------|------|----------|
| Staff | 職員情報 | 既存（拡張なし） |
| PasswordHistory | パスワード履歴 | **新規** |
| Session | セッション情報 | 既存（拡張なし） |
| SecurityLog | セキュリティログ | **新規（ログファイル）** |

---

## 1. PasswordHistory（パスワード履歴）

### 概要

職員のパスワード履歴を管理し、過去5世代のパスワード再利用を禁止するために使用。

### エンティティ定義

| フィールド | 型 | 制約 | 説明 |
|-----------|-----|------|------|
| id | ULID (26文字) | PK | パスワード履歴ID |
| staff_id | ULID (26文字) | FK, NOT NULL | 職員ID |
| password_hash | string (255) | NOT NULL | ハッシュ化済みパスワード |
| created_at | timestamp | NOT NULL | 作成日時 |

### リレーションシップ

```
Staff (1) ─────< (N) PasswordHistory
     │
     └── staff_id で関連
```

### 状態遷移

なし（作成のみ、更新なし）

### ビジネスルール

- **BR-001**: 職員ごとに最新5世代のパスワード履歴を保持
- **BR-002**: パスワード変更時に新しいパスワードが履歴と一致しないことを検証
- **BR-003**: 6世代目以降の履歴は自動的に削除

### バリデーション

| フィールド | ルール |
|-----------|--------|
| staff_id | 必須、存在する職員ID |
| password_hash | 必須、bcrypt/Argon2id 形式 |

---

## 2. Session（セッション）- 拡張なし

### 概要

Laravel 標準の sessions テーブルを使用。既存のスキーマで同時ログイン制御を実現。

### 既存スキーマ

| カラム | 型 | 説明 |
|--------|-----|------|
| id | string | セッションID |
| user_id | string (26) | 職員ID（ULID） |
| ip_address | string (45) | IPアドレス |
| user_agent | text | UserAgent |
| payload | longText | セッションデータ（暗号化済み） |
| last_activity | integer | 最終アクティビティ（UNIX timestamp） |

### 同時ログイン制御に使用するクエリ

```sql
-- アクティブセッション数を取得
SELECT COUNT(*) FROM sessions WHERE user_id = ?;

-- 最古のセッションを取得（削除対象）
SELECT id FROM sessions
WHERE user_id = ?
ORDER BY last_activity ASC
LIMIT 1;
```

### 拡張ポイント

絶対タイムアウト管理のため、セッション開始時刻を payload に含める:
- `session_start`: セッション開始時刻（UNIX timestamp）
- `last_activity`: アイドルタイムアウト用（Laravel 標準）

---

## 3. Staff（職員）- 変更なし

### 既存フィールド（参考）

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | ULID (26文字) | 職員ID |
| email | string (255) | メールアドレス |
| password | string (255) | ハッシュ化済みパスワード |
| name | string (100) | 職員名 |
| is_admin | boolean | 管理者フラグ |
| is_locked | boolean | ロック状態 |
| failed_login_attempts | integer | ログイン失敗回数 |
| locked_at | timestamp | ロック日時 |

### 同時ログイン制限値

| 職員種別 | 最大セッション数 |
|---------|----------------|
| 一般職員 (is_admin=false) | 3 |
| 管理者 (is_admin=true) | 1 |

---

## 4. SecurityLog（セキュリティログ）

### 概要

ファイルベースのログとして実装。データベーステーブルは作成しない。

### ログエントリ構造

```json
{
  "timestamp": "2026-01-06T12:00:00.000000+09:00",
  "level": "INFO|WARNING",
  "event_type": "login_success|login_failure|account_locked|password_changed|session_timeout",
  "staff_id": "01HXYZ...",
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0 ...",
  "details": {
    // イベント固有の詳細情報
  }
}
```

### イベント種別

| event_type | level | details |
|------------|-------|---------|
| login_success | INFO | なし |
| login_failure | WARNING | reason (invalid_password, account_locked, user_not_found) |
| account_locked | WARNING | failed_attempts |
| password_changed | INFO | なし |
| session_timeout | INFO | timeout_type (idle, absolute) |
| session_terminated | INFO | terminated_by (system, user, concurrent_limit) |

### ログ保持期間

| ログ種別 | 保持期間 |
|---------|---------|
| login_success | 90日 |
| login_failure | 180日 |
| password_changed | 1年 |
| account_locked | 1年 |
| session_timeout | 90日 |
| session_terminated | 90日 |

---

## データベーススキーマ

### 新規マイグレーション: password_histories

```sql
CREATE TABLE password_histories (
    id CHAR(26) PRIMARY KEY COMMENT 'パスワード履歴ID（ULID）',
    staff_id CHAR(26) NOT NULL COMMENT '職員ID',
    password_hash VARCHAR(255) NOT NULL COMMENT 'ハッシュ化済みパスワード',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',

    INDEX idx_staff_id_created_at (staff_id, created_at DESC),
    FOREIGN KEY (staff_id) REFERENCES staffs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='パスワード履歴';
```

### インデックス設計

| テーブル | インデックス | 用途 |
|---------|------------|------|
| password_histories | (staff_id, created_at DESC) | 職員ごとの履歴取得（最新順） |
| sessions | (user_id) | 既存（同時ログインチェック用） |
| sessions | (last_activity) | 既存（ガベージコレクション用） |

---

## ドメインモデル図

```
┌─────────────────────────────────────────────────────────────────┐
│                    Staff Bounded Context                         │
│                                                                 │
│  ┌───────────────┐         ┌────────────────────┐              │
│  │     Staff     │ 1     N │  PasswordHistory   │              │
│  │ (集約ルート)   │─────────│                    │              │
│  │               │         │  - id              │              │
│  │  - id         │         │  - staff_id        │              │
│  │  - email      │         │  - password_hash   │              │
│  │  - password   │         │  - created_at      │              │
│  │  - name       │         └────────────────────┘              │
│  │  - is_admin   │                                              │
│  │  - is_locked  │         ┌────────────────────┐              │
│  │  - ...        │ 1     N │     Session        │              │
│  └───────────────┘─────────│  (Laravel 標準)    │              │
│                            │                    │              │
│                            │  - id              │              │
│                            │  - user_id         │              │
│                            │  - ip_address      │              │
│                            │  - user_agent      │              │
│                            │  - last_activity   │              │
│                            └────────────────────┘              │
│                                                                 │
│  ┌────────────────────────────────────────────────────────┐    │
│  │                  Domain Services                        │    │
│  │                                                        │    │
│  │  PasswordHistoryService                                │    │
│  │  - isPasswordReused(staff, newPassword): bool          │    │
│  │  - addToHistory(staff, hashedPassword): void           │    │
│  │                                                        │    │
│  │  SessionManagerService                                 │    │
│  │  - enforceSessionLimit(staff): void                    │    │
│  │  - getActiveSessions(staff): Session[]                 │    │
│  │  - terminateSession(staff, sessionId): bool            │    │
│  │  - terminateOtherSessions(staff): void                 │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```
