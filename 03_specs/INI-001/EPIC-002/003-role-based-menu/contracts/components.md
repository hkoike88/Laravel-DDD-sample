# Component Contract: 権限別メニュー表示

**Feature**: 003-role-based-menu
**Date**: 2025-12-26

## 1. 概要

本フィーチャーで追加・変更するフロントエンドコンポーネントを定義する。

## 2. 新規コンポーネント

### 2.1 AdminMenuSection

管理者専用のメニューセクションを表示するコンポーネント。

**ファイル**: `frontend/src/features/dashboard/components/AdminMenuSection.tsx`

**Props**:

```typescript
interface AdminMenuSectionProps {
  // props なし（内部で認証ストアから権限を取得）
}
```

**表示条件**: `currentUser.is_admin === true` の場合のみ表示

**レンダリング**:

```tsx
<section className="mt-8">
  <div className="mb-6">
    <h2 className="text-xl font-bold text-gray-900">管理メニュー</h2>
    <p className="mt-1 text-sm text-gray-600">管理者専用の機能</p>
  </div>
  <MenuGrid items={adminMenuItems} />
</section>
```

**依存**:
- `useAuthStore` - 認証ストア
- `MenuGrid` - 既存のメニューグリッドコンポーネント
- `adminMenuItems` - 管理メニュー項目定義

---

### 2.2 AdminGuard

管理者専用ルートを保護するガードコンポーネント。

**ファイル**: `frontend/src/components/guards/AdminGuard.tsx`

**Props**:

```typescript
interface AdminGuardProps {
  children: React.ReactNode
}
```

**動作**:
1. 認証ストアから `currentUser` と `isLoading` を取得
2. `isLoading` が true の場合はローディング表示
3. `currentUser` が null の場合はログインページへリダイレクト
4. `currentUser.is_admin` が false の場合は 403 エラーページを表示
5. 管理者の場合は `children` をレンダリング

**レンダリング（403 の場合）**:

```tsx
<div className="flex min-h-screen items-center justify-center">
  <div className="text-center">
    <h1 className="text-4xl font-bold text-gray-900">403</h1>
    <p className="mt-2 text-gray-600">この操作を行う権限がありません</p>
    <Link to="/dashboard" className="mt-4 inline-block text-blue-600">
      ダッシュボードに戻る
    </Link>
  </div>
</div>
```

---

### 2.3 ForbiddenPage（オプション）

403 エラー専用ページ。AdminGuard から参照される。

**ファイル**: `frontend/src/pages/errors/ForbiddenPage.tsx`

**Props**: なし

**レンダリング**:

```tsx
<MainLayout>
  <div className="flex min-h-[calc(100vh-200px)] items-center justify-center">
    <div className="text-center">
      <h1 className="text-6xl font-bold text-red-600">403</h1>
      <h2 className="mt-4 text-2xl font-semibold text-gray-900">
        アクセス権限がありません
      </h2>
      <p className="mt-2 text-gray-600">
        この操作を行う権限がありません
      </p>
      <Link
        to="/dashboard"
        className="mt-6 inline-block rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
      >
        ダッシュボードに戻る
      </Link>
    </div>
  </div>
</MainLayout>
```

## 3. 変更コンポーネント

### 3.1 DashboardPage

**ファイル**: `frontend/src/features/dashboard/pages/DashboardPage.tsx`

**変更内容**:
- `AdminMenuSection` のインポートと条件付き表示を追加

**変更前**:

```tsx
export function DashboardPage() {
  const currentUser = useAuthStore((state) => state.currentUser)

  return (
    <MainLayout>
      <div className="bg-gray-100 py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {currentUser && (
            <div className="mb-8">
              <WelcomeMessage userName={currentUser.name} />
            </div>
          )}
          <div className="mb-6">
            <h2 className="text-xl font-bold text-gray-900">業務メニュー</h2>
            <p className="mt-1 text-sm text-gray-600">操作を選択してください</p>
          </div>
          <MenuGrid items={menuItems} />
        </div>
      </div>
    </MainLayout>
  )
}
```

**変更後**:

```tsx
export function DashboardPage() {
  const currentUser = useAuthStore((state) => state.currentUser)

  return (
    <MainLayout>
      <div className="bg-gray-100 py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {currentUser && (
            <div className="mb-8">
              <WelcomeMessage userName={currentUser.name} />
            </div>
          )}
          <div className="mb-6">
            <h2 className="text-xl font-bold text-gray-900">業務メニュー</h2>
            <p className="mt-1 text-sm text-gray-600">操作を選択してください</p>
          </div>
          <MenuGrid items={menuItems} />

          {/* 管理者のみ管理メニューを表示 */}
          {currentUser?.is_admin && <AdminMenuSection />}
        </div>
      </div>
    </MainLayout>
  )
}
```

## 4. 新規定数ファイル

### 4.1 adminMenuItems

**ファイル**: `frontend/src/features/dashboard/constants/adminMenuItems.tsx`

```typescript
import type { MenuItem } from '../types/menu'
import { UsersIcon } from '../components/icons/MenuIcons'

/**
 * 管理者専用メニュー項目
 */
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

## 5. 型定義の更新

### 5.1 Staff 型

**ファイル**: `frontend/src/features/auth/types/auth.ts`

**変更前**:

```typescript
export interface Staff {
  id: string
  name: string
  email: string
}
```

**変更後**:

```typescript
export interface Staff {
  id: string
  name: string
  email: string
  /** 管理者フラグ */
  is_admin: boolean
}
```

## 6. ルーター設定

### 6.1 管理者専用ルートの追加

**ファイル**: `frontend/src/router/index.tsx`（または routes.tsx）

```tsx
import { AdminGuard } from '@/components/guards/AdminGuard'

// 管理者専用ルート
<Route
  path="/staff/accounts"
  element={
    <AdminGuard>
      <StaffAccountsPage />
    </AdminGuard>
  }
/>
```

**注意**: `StaffAccountsPage` は後続の職員管理フィーチャーで実装。本フィーチャーではプレースホルダーページを配置。
