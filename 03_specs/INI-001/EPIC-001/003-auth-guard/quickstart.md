# Quickstart: 認証ガードの実装

**Branch**: `005-auth-guard` | **Date**: 2025-12-26

## 概要

本ドキュメントは認証ガード機能の実装を開始するためのクイックスタートガイドである。

## 前提条件

- Node.js 20.x
- Docker Compose（バックエンド API 起動用）
- 既存の認証機能（003-login-ui）が実装済み

## 環境セットアップ

### 1. 依存関係のインストール

```bash
cd frontend
npm install
```

### 2. 開発サーバーの起動

```bash
# バックエンド（Docker）
docker compose up -d

# フロントエンド
npm run dev
```

## 既存実装の確認

認証ガードの基本機能は既に実装済み。以下のファイルを確認：

### コンポーネント

| ファイル | 説明 |
|---------|------|
| `src/features/auth/components/ProtectedRoute.tsx` | 認証必須ページのガード |
| `src/features/auth/components/GuestRoute.tsx` | ゲスト専用ページのガード |
| `src/features/auth/components/AuthProvider.tsx` | 認証初期化プロバイダー |

### フック

| ファイル | 説明 |
|---------|------|
| `src/features/auth/hooks/useAuthCheck.ts` | 認証状態確認フック |
| `src/features/auth/hooks/useLogin.ts` | ログインフック |
| `src/features/auth/hooks/useLogout.ts` | ログアウトフック |

### ストア

| ファイル | 説明 |
|---------|------|
| `src/features/auth/stores/authStore.ts` | Zustand 認証ストア |

## 追加実装タスク

### 1. useAuth フックの実装

**ファイル**: `src/features/auth/hooks/useAuth.ts`

```typescript
/**
 * 認証状態アクセスフック
 *
 * authStore のラッパーフック。
 * 認証状態と関連アクションを一括で提供。
 */
import { useAuthStore } from '../stores/authStore'

export function useAuth() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)
  const currentUser = useAuthStore((state) => state.currentUser)
  const isLoading = useAuthStore((state) => state.isLoading)
  const setAuthenticated = useAuthStore((state) => state.setAuthenticated)
  const clearAuthentication = useAuthStore((state) => state.clearAuthentication)
  const setLoading = useAuthStore((state) => state.setLoading)

  return {
    isAuthenticated,
    currentUser,
    isLoading,
    setAuthenticated,
    clearAuthentication,
    setLoading,
  }
}
```

### 2. 単体テストの実装

**ファイル**: `src/features/auth/hooks/useAuth.test.tsx`

```typescript
import { describe, test, expect, beforeEach } from 'vitest'
import { renderHook, act } from '@testing-library/react'
import { useAuth } from './useAuth'
import { useAuthStore } from '../stores/authStore'

describe('useAuth', () => {
  beforeEach(() => {
    useAuthStore.setState({
      isAuthenticated: false,
      currentUser: null,
      isLoading: true,
    })
  })

  test('初期状態を正しく返す', () => {
    const { result } = renderHook(() => useAuth())

    expect(result.current.isAuthenticated).toBe(false)
    expect(result.current.currentUser).toBeNull()
    expect(result.current.isLoading).toBe(true)
  })

  test('setAuthenticated でユーザー情報を設定', () => {
    const { result } = renderHook(() => useAuth())
    const mockUser = { id: '1', name: 'Test', email: 'test@example.com' }

    act(() => {
      result.current.setAuthenticated(mockUser)
    })

    expect(result.current.isAuthenticated).toBe(true)
    expect(result.current.currentUser).toEqual(mockUser)
    expect(result.current.isLoading).toBe(false)
  })
})
```

### 3. E2E テストの実装

**ファイル**: `tests/e2e/auth-guard.spec.ts`

```typescript
import { test, expect, type Page, type Route } from '@playwright/test'

// モック設定とテストケースは research.md を参照
```

## テスト実行

### 単体テスト

```bash
# 全テスト実行
npm run test:run

# 認証関連のみ
npm run test -- --grep "useAuth"

# カバレッジ付き
npm run test:coverage
```

### E2E テスト

```bash
# 全 E2E テスト
npm run test:e2e

# 認証ガードのみ
npm run test:e2e -- auth-guard.spec.ts

# UI モード
npm run test:e2e:ui
```

## 成功基準の検証

| 基準 | 検証方法 |
|-----|---------|
| SC-001: リダイレクト 1 秒以内 | E2E テストで計測 |
| SC-002: ダッシュボードリダイレクト 1 秒以内 | E2E テストで計測 |
| SC-003: 認証確認 3 秒以内 | E2E テストで計測 |
| SC-004: 全保護ページが ProtectedRoute で保護 | router.tsx を確認 |
| SC-005: テストカバレッジ 80% 以上 | `npm run test:coverage` |
| SC-006: E2E で全受け入れ条件を検証 | `npm run test:e2e` |

## トラブルシューティング

### 認証状態が保持されない

1. バックエンドが起動しているか確認: `docker compose ps`
2. CORS 設定を確認: `backend/.env` の `SANCTUM_STATEFUL_DOMAINS`
3. Cookie が設定されているか DevTools で確認

### テストが失敗する

1. モック設定を確認
2. Zustand ストアの状態リセットを確認（`beforeEach`）
3. 非同期処理の待機を確認（`waitFor`）

## 次のステップ

1. `/speckit.tasks` を実行してタスクリストを生成
2. 各タスクを順番に実装
3. テストを実行して成功基準を満たすことを確認
