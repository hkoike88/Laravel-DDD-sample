# Data Model: 職員ログアウト機能

**Feature Branch**: `001-staff-logout`
**Date**: 2026-01-06

---

## 概要

ログアウト機能は既存のデータモデルを使用し、新規エンティティの追加は不要です。
本ドキュメントでは、関連する既存エンティティと状態遷移を記録します。

---

## 既存エンティティ（参照のみ）

### Session

**説明**: 職員の認証セッションを管理

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | string (primary) | セッションID |
| user_id | string (nullable) | 職員ID（ULID） |
| ip_address | string (nullable) | クライアントIPアドレス |
| user_agent | text (nullable) | ユーザーエージェント |
| payload | text | セッションデータ（暗号化済み） |
| last_activity | integer | 最終アクティビティのタイムスタンプ |

**ログアウト時の動作**:
- セッションレコードは `invalidate()` により無効化される
- 新しいセッションID が `regenerateToken()` により生成される

### Staff（既存）

**説明**: システムにログインする職員

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | string (ULID) | 職員ID |
| name | string | 職員名 |
| email | string (unique) | メールアドレス |
| is_locked | boolean | アカウントロック状態 |

**ログアウト時の動作**:
- Staff レコード自体は変更されない
- Session との関連が切断される

---

## フロントエンド状態

### AuthStore（Zustand）

**説明**: クライアント側の認証状態を管理

| プロパティ | 型 | 説明 |
|-----------|-----|------|
| staff | Staff \| null | 認証済み職員情報 |
| isAuthenticated | boolean | 認証状態フラグ |
| isLoading | boolean | 読み込み中フラグ |

**ログアウト時の動作**:
- `clearAuthentication()` により `staff = null`, `isAuthenticated = false` に設定

### Navigation State（追加予定）

**説明**: React Router のナビゲーション状態

| プロパティ | 型 | 説明 |
|-----------|-----|------|
| loggedOut | boolean | ログアウト完了フラグ |

**使用タイミング**:
- ログアウト成功時に `navigate('/login', { state: { loggedOut: true } })` で設定
- LoginPage で `useLocation()` により取得

---

## 状態遷移図

### 認証状態

```
                     ┌──────────────────┐
                     │   Authenticated  │
                     │  (ログイン済み)   │
                     └────────┬─────────┘
                              │
                              │ ログアウト操作
                              ▼
┌─────────────────────────────────────────────────┐
│              ログアウト処理中                      │
│                                                 │
│  1. POST /api/auth/logout                       │
│  2. セッション無効化 (invalidate)                 │
│  3. CSRFトークン再生成 (regenerateToken)          │
│  4. クライアント状態クリア (clearAuthentication)  │
│  5. リダイレクト (/login)                        │
└────────────────────────┬────────────────────────┘
                         │
                         ▼
             ┌───────────────────────┐
             │    Unauthenticated    │
             │    (未認証状態)        │
             └───────────────────────┘
```

### ログイン画面メッセージ状態

```
┌──────────────────┐
│  リダイレクト完了  │
│ state.loggedOut  │
│    = true        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐     5秒後      ┌──────────────────┐
│  メッセージ表示   │ ────────────> │   メッセージ非表示  │
│  "ログアウト      │               │   通常表示         │
│   しました"       │               │                   │
└──────────────────┘               └──────────────────┘
```

---

## データフロー

### ログアウト処理シーケンス

```
User          Frontend           Backend           Database
 │                │                 │                 │
 │  Click         │                 │                 │
 │  Logout        │                 │                 │
 │ ───────────>   │                 │                 │
 │                │                 │                 │
 │                │  POST /api/     │                 │
 │                │  auth/logout    │                 │
 │                │ ───────────────>│                 │
 │                │                 │                 │
 │                │                 │  Invalidate     │
 │                │                 │  Session        │
 │                │                 │ ───────────────>│
 │                │                 │                 │
 │                │                 │  Regenerate     │
 │                │                 │  Token          │
 │                │                 │ ───────────────>│
 │                │                 │                 │
 │                │  200 OK         │                 │
 │                │  "ログアウト     │                 │
 │                │   しました"     │                 │
 │                │ <───────────────│                 │
 │                │                 │                 │
 │                │  Clear Auth     │                 │
 │                │  State          │                 │
 │                │ ──────┐         │                 │
 │                │       │         │                 │
 │                │ <─────┘         │                 │
 │                │                 │                 │
 │  Redirect to   │                 │                 │
 │  /login with   │                 │                 │
 │  state         │                 │                 │
 │ <───────────   │                 │                 │
 │                │                 │                 │
 │  Show Success  │                 │                 │
 │  Message       │                 │                 │
 │ <───────────   │                 │                 │
 │                │                 │                 │
```

---

## 新規追加なし

本機能では新規のデータモデル（テーブル、エンティティ）の追加は不要です。
既存の Session、Staff エンティティと AuthStore で要件を満たします。
