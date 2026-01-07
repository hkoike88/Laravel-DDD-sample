# Data Model: 認証ガードの実装

**Branch**: `005-auth-guard` | **Date**: 2025-12-26

## 概要

本ドキュメントは認証ガード機能のデータモデルを定義する。本フィーチャーはフロントエンドの状態管理が中心であり、バックエンドのデータベースへの変更はない。

## エンティティ定義

### 1. Staff（職員）

ログイン中のユーザーを表すエンティティ。バックエンドから取得され、フロントエンドで保持される。

| フィールド | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| id | string | ✅ | 職員ID（ULID形式、26文字） |
| name | string | ✅ | 職員名（最大100文字） |
| email | string | ✅ | メールアドレス（最大255文字） |

**TypeScript 定義** (既存: `frontend/src/features/auth/types/auth.ts`)

```typescript
export interface Staff {
  id: string
  name: string
  email: string
}
```

### 2. AuthState（認証状態）

アプリケーション全体の認証状態を表す。Zustand ストアで管理される。

| フィールド | 型 | 必須 | 初期値 | 説明 |
|-----------|-----|------|--------|------|
| isAuthenticated | boolean | ✅ | false | 認証済みフラグ |
| currentUser | Staff \| null | ✅ | null | 現在のユーザー情報 |
| isLoading | boolean | ✅ | true | 認証確認中フラグ |

**TypeScript 定義** (既存: `frontend/src/features/auth/types/auth.ts`)

```typescript
export interface AuthState {
  isAuthenticated: boolean
  currentUser: Staff | null
  isLoading: boolean
}
```

### 3. AuthActions（認証アクション）

認証状態を操作するためのアクション群。

| アクション | シグネチャ | 説明 |
|-----------|-----------|------|
| setAuthenticated | (user: Staff) => void | 認証成功時にユーザー情報を設定 |
| clearAuthentication | () => void | 認証クリア（ログアウト時） |
| setLoading | (loading: boolean) => void | ローディング状態を設定 |

**TypeScript 定義** (既存: `frontend/src/features/auth/types/auth.ts`)

```typescript
export interface AuthActions {
  setAuthenticated: (user: Staff) => void
  clearAuthentication: () => void
  setLoading: (loading: boolean) => void
}
```

### 4. AuthStore（認証ストア）

AuthState と AuthActions を統合した Zustand ストア型。

```typescript
export type AuthStore = AuthState & AuthActions
```

## 状態遷移図

```
┌─────────────────┐
│   Initial       │
│ isLoading: true │
│ isAuthenticated:│
│   false         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     認証API成功      ┌─────────────────┐
│   Checking      │ ─────────────────▶ │  Authenticated   │
│ isLoading: true │                      │ isLoading: false │
│ isAuthenticated:│                      │ isAuthenticated: │
│   false         │                      │   true           │
└────────┬────────┘                      │ currentUser: {}  │
         │                               └────────┬─────────┘
         │ 認証API失敗                            │
         ▼                                        │ ログアウト
┌─────────────────┐                              ▼
│ Unauthenticated │◀──────────────────────────────┘
│ isLoading: false│
│ isAuthenticated:│
│   false         │
│ currentUser:null│
└─────────────────┘
```

## コンポーネント間のデータフロー

```
┌─────────────────────────────────────────────────────────────────┐
│                        App (root)                                │
│                            │                                     │
│    ┌───────────────────────┼───────────────────────────┐        │
│    │              AuthProvider                          │        │
│    │                   │                                │        │
│    │        ┌──────────┼──────────┐                    │        │
│    │        │    useAuthCheck     │                    │        │
│    │        │  (TanStack Query)   │                    │        │
│    │        │          │          │                    │        │
│    │        │    ┌─────┴─────┐    │                    │        │
│    │        │    │ authStore │    │                    │        │
│    │        │    │ (Zustand) │    │                    │        │
│    │        │    └─────┬─────┘    │                    │        │
│    │        └──────────┼──────────┘                    │        │
│    │                   │                                │        │
│    │    ┌──────────────┼──────────────┐               │        │
│    │    │              │              │               │        │
│    │    ▼              ▼              ▼               │        │
│    │ ProtectedRoute GuestRoute    useAuth             │        │
│    │    │              │           (新規)             │        │
│    │    │              │              │               │        │
│    │    ▼              ▼              ▼               │        │
│    │ [保護ページ]  [ゲストページ]  [任意のコンポーネント]        │        │
│    │ - Dashboard   - Login                            │        │
│    │ - Books/Manage                                   │        │
│    │ - etc.                                           │        │
│    └────────────────────────────────────────────────────┘        │
└─────────────────────────────────────────────────────────────────┘
```

## バリデーションルール

### Staff

| フィールド | ルール |
|-----------|--------|
| id | ULID形式（26文字の英数字） |
| name | 1〜100文字 |
| email | メール形式、最大255文字 |

## API レスポンス形式

### GET /api/auth/user（認証ユーザー取得）

**成功時 (200)**
```json
{
  "data": {
    "id": "01HXYZ1234567890123456789A",
    "name": "山田太郎",
    "email": "staff@example.com"
  }
}
```

**未認証時 (401)**
```json
{
  "message": "Unauthenticated."
}
```

## 関連ファイル

| ファイル | 説明 |
|---------|------|
| `frontend/src/features/auth/types/auth.ts` | 型定義（既存） |
| `frontend/src/features/auth/stores/authStore.ts` | Zustand ストア（既存） |
| `frontend/src/features/auth/hooks/useAuth.ts` | ラッパーフック（新規追加） |
| `frontend/src/features/auth/hooks/useAuthCheck.ts` | 認証確認フック（既存） |
| `frontend/src/features/auth/components/ProtectedRoute.tsx` | 保護ルート（既存） |
| `frontend/src/features/auth/components/GuestRoute.tsx` | ゲストルート（既存） |
| `frontend/src/features/auth/components/AuthProvider.tsx` | 認証プロバイダー（既存） |
