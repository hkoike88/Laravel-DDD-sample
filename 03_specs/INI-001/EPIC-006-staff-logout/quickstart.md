# Quickstart: 職員ログアウト機能

**Feature Branch**: `001-staff-logout`
**Date**: 2026-01-06

---

## 概要

この機能は、職員がシステムからログアウトし、セッションを安全に終了する機能を提供します。
ログアウト後はログイン画面にリダイレクトされ、完了メッセージが表示されます。

---

## 実装概要

### バックエンド（実装済み）

| ファイル | 状態 | 説明 |
|---------|------|------|
| `AuthController::logout` | 実装済み | ログアウトエンドポイント |
| `LogoutUseCase` | 実装済み | ログアウト処理ユースケース |

### フロントエンド

| ファイル | 状態 | 変更内容 |
|---------|------|---------|
| `useLogout.ts` | 要修正 | navigate に state を追加 |
| `LoginPage.tsx` | 要修正 | ログアウト完了メッセージ表示を追加 |
| テスト | 要追加 | 新機能のテストを追加 |

---

## 実装手順

### Step 1: useLogout フックの更新

```typescript
// frontend/src/features/auth/hooks/useLogout.ts

// 変更前
navigate('/login', { replace: true })

// 変更後
navigate('/login', { replace: true, state: { loggedOut: true } })
```

### Step 2: LoginPage にメッセージ表示を追加

```typescript
// frontend/src/features/auth/pages/LoginPage.tsx

import { useLocation } from 'react-router-dom'
import { useState, useEffect } from 'react'

interface LocationState {
  loggedOut?: boolean
}

export function LoginPage() {
  const location = useLocation()
  const state = location.state as LocationState | null
  const [showLogoutMessage, setShowLogoutMessage] = useState(state?.loggedOut ?? false)

  // 5秒後にメッセージを非表示
  useEffect(() => {
    if (showLogoutMessage) {
      const timer = setTimeout(() => {
        setShowLogoutMessage(false)
      }, 5000)
      return () => clearTimeout(timer)
    }
  }, [showLogoutMessage])

  // stateをクリア（ブラウザ履歴から削除）
  useEffect(() => {
    if (state?.loggedOut) {
      window.history.replaceState({}, document.title)
    }
  }, [state])

  return (
    <div>
      {/* ログアウト完了メッセージ */}
      {showLogoutMessage && (
        <div
          role="alert"
          className="mb-4 rounded-md bg-green-50 p-4 text-green-800"
        >
          ログアウトしました
        </div>
      )}

      {/* 既存のログインフォーム */}
      ...
    </div>
  )
}
```

### Step 3: テストの追加

```typescript
// frontend/src/features/auth/pages/LoginPage.test.tsx

describe('LoginPage', () => {
  it('should display logout success message when redirected after logout', () => {
    // state.loggedOut = true でレンダリング
    render(
      <MemoryRouter initialEntries={[{ pathname: '/login', state: { loggedOut: true } }]}>
        <LoginPage />
      </MemoryRouter>
    )

    expect(screen.getByText('ログアウトしました')).toBeInTheDocument()
  })

  it('should hide logout message after 5 seconds', async () => {
    jest.useFakeTimers()

    render(/* ... */)

    expect(screen.getByText('ログアウトしました')).toBeInTheDocument()

    act(() => {
      jest.advanceTimersByTime(5000)
    })

    expect(screen.queryByText('ログアウトしました')).not.toBeInTheDocument()

    jest.useRealTimers()
  })
})
```

---

## 動作確認手順

### 1. 開発サーバーの起動

```bash
# バックエンド
docker compose up -d

# フロントエンド
cd frontend && npm run dev
```

### 2. ログアウト動作確認

1. `http://localhost:5173/login` にアクセス
2. 職員アカウントでログイン
3. ヘッダーの職員名をクリック
4. ドロップダウンメニューから「ログアウト」をクリック
5. ログイン画面にリダイレクトされることを確認
6. 「ログアウトしました」メッセージが表示されることを確認
7. 5秒後にメッセージが消えることを確認
8. ブラウザの戻るボタンを押してもダッシュボードに戻れないことを確認

### 3. エッジケース確認

- ネットワーク切断時: エラーでもログイン画面に遷移すること
- 複数タブ: 1つのタブでログアウト後、他のタブでも認証が無効になること
- セッション切れ: ログアウトボタン押下時にエラーなく遷移すること

---

## テスト実行

```bash
# フロントエンドテスト
cd frontend && npm run test

# 特定テストの実行
cd frontend && npm run test -- --testPathPattern="LoginPage"
cd frontend && npm run test -- --testPathPattern="useLogout"
```

---

## 関連ファイル

### バックエンド

- `backend/app/Http/Controllers/Auth/AuthController.php`
- `backend/packages/Domain/Staff/Application/UseCases/Auth/LogoutUseCase.php`
- `backend/routes/api.php`

### フロントエンド

- `frontend/src/features/auth/hooks/useLogout.ts`
- `frontend/src/features/auth/pages/LoginPage.tsx`
- `frontend/src/features/auth/api/authApi.ts`
- `frontend/src/features/auth/stores/authStore.ts`
- `frontend/src/components/layout/Header.tsx`
