# Data Model: ダッシュボード画面

**Feature**: 004-dashboard-ui
**Date**: 2025-12-25

## Overview

ダッシュボード画面はフロントエンドのみの実装であり、新規のバックエンドエンティティは追加しない。既存の認証情報（Staff）を参照し、メニュー項目はフロントエンドで静的に定義する。

## Entities

### 1. Staff（既存・参照のみ）

認証済み職員の情報。003-login-ui で定義済み。

| Field | Type | Description |
|-------|------|-------------|
| id | string (ULID) | 職員ID |
| name | string | 職員名 |
| email | string | メールアドレス |

**Source**: `frontend/src/features/auth/types/auth.ts`

### 2. MenuItem（フロントエンド定義）

ダッシュボードに表示する業務メニュー項目。

| Field | Type | Description |
|-------|------|-------------|
| id | string | メニュー識別子（例: 'books', 'loans'） |
| label | string | 表示ラベル（例: '蔵書管理'） |
| icon | ReactNode | メニューアイコン |
| path | string | 遷移先パス（例: '/books'） |
| enabled | boolean | 有効/無効状態 |
| description | string? | メニューの説明（オプション） |

**定義場所**: `frontend/src/features/dashboard/types/menu.ts`

### 3. NavigationItem（フロントエンド定義）

ヘッダーナビゲーションに表示するリンク項目。

| Field | Type | Description |
|-------|------|-------------|
| id | string | ナビ識別子 |
| label | string | 表示ラベル |
| path | string | 遷移先パス |

**定義場所**: `frontend/src/components/layout/Header.tsx`（内部定義）

## State Management

### AuthStore（既存・拡張なし）

認証状態の管理。003-login-ui で実装済み。

```typescript
interface AuthStore {
  isAuthenticated: boolean
  currentUser: Staff | null
  isLoading: boolean
  setAuthenticated: (user: Staff) => void
  clearAuthentication: () => void
  setLoading: (loading: boolean) => void
}
```

**Source**: `frontend/src/features/auth/stores/authStore.ts`

## Static Data

### Menu Items

```typescript
const menuItems: MenuItem[] = [
  { id: 'books', label: '蔵書管理', icon: <BookIcon />, path: '/books', enabled: true },
  { id: 'loans', label: '貸出処理', icon: <LoanIcon />, path: '/loans/new', enabled: true },
  { id: 'returns', label: '返却処理', icon: <ReturnIcon />, path: '/loans/return', enabled: true },
  { id: 'users', label: '利用者管理', icon: <UserIcon />, path: '/users', enabled: true },
  { id: 'reservations', label: '予約管理', icon: <ReservationIcon />, path: '/reservations', enabled: true },
  { id: 'stats', label: '統計', icon: <StatsIcon />, path: '/stats', enabled: false },
]
```

### Navigation Items

```typescript
const navigationItems: NavigationItem[] = [
  { id: 'books', label: '蔵書管理', path: '/books' },
  { id: 'loans', label: '貸出・返却', path: '/loans' },
  { id: 'users', label: '利用者管理', path: '/users' },
]
```

## Relationships

```
┌─────────────────┐      references      ┌─────────────────┐
│    AuthStore    │─────────────────────▶│      Staff      │
└─────────────────┘                      └─────────────────┘
        │
        │ provides user info
        ▼
┌─────────────────┐
│  DashboardPage  │
└─────────────────┘
        │
        │ renders
        ▼
┌─────────────────┐      uses      ┌─────────────────┐
│    MenuGrid     │───────────────▶│    MenuItem     │
└─────────────────┘                └─────────────────┘
```

## No Backend Changes

このフィーチャーでは以下のバックエンド変更は不要:
- 新規 API エンドポイント: なし（ログアウト API は既存）
- データベーススキーマ変更: なし
- 新規エンティティ: なし

全てフロントエンドの UI コンポーネントとして実装する。
