# Quickstart: 権限別メニュー表示

**Feature**: 003-role-based-menu
**Date**: 2025-12-26

## 1. 概要

本フィーチャーの実装手順を簡潔にまとめる。

## 2. 前提条件

- [x] セッション管理機能（005-session-management）実装済み
- [x] Staff エンティティに `is_admin` フラグ実装済み
- [x] 認証 API が `is_admin` を返却済み
- [x] ダッシュボードページ実装済み

## 3. 実装ステップ

### Step 1: フロントエンド型定義の更新

`frontend/src/features/auth/types/auth.ts` の `Staff` 型に `is_admin` を追加。

```typescript
export interface Staff {
  id: string
  name: string
  email: string
  is_admin: boolean  // 追加
}
```

### Step 2: 管理メニュー項目の定義

`frontend/src/features/dashboard/constants/adminMenuItems.tsx` を新規作成。

```typescript
export const adminMenuItems: MenuItem[] = [
  {
    id: 'staff-accounts',
    label: '職員管理',
    icon: <UsersIcon />,
    path: '/staff/accounts',
    enabled: true,
    description: '職員アカウントの管理',
  },
]
```

### Step 3: AdminMenuSection コンポーネント作成

`frontend/src/features/dashboard/components/AdminMenuSection.tsx` を新規作成。

### Step 4: DashboardPage の更新

`frontend/src/features/dashboard/pages/DashboardPage.tsx` に `AdminMenuSection` を条件付きで追加。

```tsx
{currentUser?.is_admin && <AdminMenuSection />}
```

### Step 5: AdminGuard コンポーネント作成

`frontend/src/components/guards/AdminGuard.tsx` を新規作成。

### Step 6: ForbiddenPage 作成

`frontend/src/pages/errors/ForbiddenPage.tsx` を新規作成。

### Step 7: ルーター設定

管理者専用ルートを追加し、AdminGuard で保護。

### Step 8: バックエンド RequireAdmin ミドルウェア作成

`backend/app/Http/Middleware/RequireAdmin.php` を新規作成。

### Step 9: ミドルウェア登録

`backend/bootstrap/app.php` に `require.admin` エイリアスを登録。

### Step 10: 管理者専用ルート追加

`backend/routes/api.php` に `/api/staff/accounts` ルートを追加。

### Step 11: テスト作成・実行

- バックエンド: `AdminAccessTest.php`
- フロントエンド: `AdminMenuSection.test.tsx`

## 4. 動作確認

### 管理者ログイン時

1. ログインページで管理者アカウントでログイン
2. ダッシュボードに「業務メニュー」と「管理メニュー」が表示される
3. 「職員管理」をクリックして `/staff/accounts` に遷移できる

### 一般職員ログイン時

1. ログインページで一般職員アカウントでログイン
2. ダッシュボードに「業務メニュー」のみ表示される
3. ブラウザで `/staff/accounts` に直接アクセスすると 403 ページが表示される

## 5. テストアカウント

```
管理者:
  Email: admin@example.com
  Password: password123

一般職員:
  Email: staff@example.com
  Password: password123
```

## 6. 関連ドキュメント

- [仕様書](./spec.md)
- [実装計画](./plan.md)
- [調査結果](./research.md)
- [データモデル](./data-model.md)
- [API コントラクト](./contracts/api.md)
- [コンポーネントコントラクト](./contracts/components.md)
