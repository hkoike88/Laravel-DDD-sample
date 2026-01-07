# Research: 認証ガードの実装

**Branch**: `005-auth-guard` | **Date**: 2025-12-26

## 概要

本ドキュメントは認証ガード機能の実装に必要な技術調査結果をまとめる。既存実装が大部分を占めるため、主に追加実装（useAuth フック、テスト）に関するベストプラクティスを調査。

## 1. useAuth フックの設計パターン

### Decision: Zustand ストアのラッパーフックとして実装

### Rationale

- 既存の `useAuthStore` は低レベルの Zustand セレクターパターンを使用
- `useAuth` フックを導入することで、以下のメリットを実現：
  - 認証状態と派生値を一括で取得可能
  - コンポーネントからのアクセスを簡素化
  - 将来的な機能拡張（ログイン後リダイレクト先の保持など）への対応が容易

### Alternatives Considered

| 選択肢 | 評価 | 却下理由 |
|--------|------|----------|
| useAuthStore を直接使用し続ける | ❌ | コンポーネントごとにセレクター記述が必要、設計整合性が低下 |
| Context API に移行 | ❌ | 既存の Zustand 実装を破棄する必要があり、過剰な変更 |
| **useAuth ラッパーフック** | ✅ | 既存実装を維持しつつ、使いやすさを向上 |

### 実装パターン

```typescript
/**
 * useAuth フック
 *
 * authStore のラッパーフック。
 * 認証状態と関連アクションを一括で提供。
 */
export function useAuth() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)
  const currentUser = useAuthStore((state) => state.currentUser)
  const isLoading = useAuthStore((state) => state.isLoading)
  const setAuthenticated = useAuthStore((state) => state.setAuthenticated)
  const clearAuthentication = useAuthStore((state) => state.clearAuthentication)
  const setLoading = useAuthStore((state) => state.setLoading)

  return {
    // 状態
    isAuthenticated,
    currentUser,
    isLoading,
    // アクション
    setAuthenticated,
    clearAuthentication,
    setLoading,
  }
}
```

## 2. 認証ガードコンポーネントのテスト戦略

### Decision: Testing Library + Vitest による単体テスト

### Rationale

- 既存プロジェクトで Vitest + Testing Library が導入済み
- コンポーネントのレンダリング結果と振る舞いをテスト可能
- モック機能が充実しており、Zustand ストアのモックが容易

### テストケース設計

#### ProtectedRoute テスト

| テストケース | 期待結果 |
|-------------|---------|
| 認証済み状態で children を表示 | children がレンダリングされる |
| 未認証状態でログイン画面にリダイレクト | `/login` へ Navigate |
| ローディング中はローディング表示 | 「認証確認中...」が表示される |

#### GuestRoute テスト

| テストケース | 期待結果 |
|-------------|---------|
| 未認証状態で children を表示 | children がレンダリングされる |
| 認証済み状態でダッシュボードにリダイレクト | `/dashboard` へ Navigate |
| ローディング中はローディング表示 | 「認証確認中...」が表示される |

#### useAuth フックテスト

| テストケース | 期待結果 |
|-------------|---------|
| 初期状態を正しく返す | isAuthenticated: false, currentUser: null, isLoading: true |
| setAuthenticated でユーザー設定 | isAuthenticated: true, currentUser が設定される |
| clearAuthentication で認証クリア | isAuthenticated: false, currentUser: null |

## 3. E2E テスト戦略

### Decision: 既存の Playwright テストを拡充し、認証ガード専用のテストスイートを追加

### Rationale

- 既存の `login.spec.ts` と `dashboard.spec.ts` に認証リダイレクトの基本テストが存在
- 認証ガード専用のテストスイートを新設し、全保護ページのリダイレクト動作を網羅

### Alternatives Considered

| 選択肢 | 評価 | 却下理由 |
|--------|------|----------|
| 既存テストのみ使用 | ❌ | 全保護ページの網羅性が不十分 |
| **専用テストスイート追加** | ✅ | 網羅的なテストと保守性を両立 |

### E2E テストケース設計

#### auth-guard.spec.ts

| カテゴリ | テストケース | 期待結果 |
|---------|-------------|---------|
| 未認証リダイレクト | `/dashboard` アクセス | `/login` へリダイレクト |
| 未認証リダイレクト | `/books/manage` アクセス | `/login` へリダイレクト |
| 未認証リダイレクト | `/loans/checkout` アクセス | `/login` へリダイレクト |
| 未認証リダイレクト | `/loans/return` アクセス | `/login` へリダイレクト |
| 未認証リダイレクト | `/users` アクセス | `/login` へリダイレクト |
| 未認証リダイレクト | `/reservations` アクセス | `/login` へリダイレクト |
| 認証済みリダイレクト | `/login` アクセス | `/dashboard` へリダイレクト |
| 認証状態維持 | 複数ページ遷移 | 認証状態が維持される |
| ログアウト後 | 保護ページアクセス | `/login` へリダイレクト |

## 4. Zustand ストアのテスト戦略

### Decision: ストアを直接インポートしてテスト

### Rationale

- Zustand ストアはグローバルシングルトンとして動作
- テスト間の状態汚染を防ぐため、各テスト前に状態をリセット
- `useAuthStore.setState()` を使用して状態を直接操作可能

### テストパターン

```typescript
import { useAuthStore } from '../stores/authStore'

describe('authStore', () => {
  beforeEach(() => {
    // 各テスト前に状態をリセット
    useAuthStore.setState({
      isAuthenticated: false,
      currentUser: null,
      isLoading: true,
    })
  })

  test('setAuthenticated sets user and isAuthenticated', () => {
    const mockUser = { id: '1', name: 'Test', email: 'test@example.com' }
    useAuthStore.getState().setAuthenticated(mockUser)

    expect(useAuthStore.getState().isAuthenticated).toBe(true)
    expect(useAuthStore.getState().currentUser).toEqual(mockUser)
    expect(useAuthStore.getState().isLoading).toBe(false)
  })
})
```

## 5. React Router v7 との統合

### Decision: 既存の Navigate コンポーネントパターンを維持

### Rationale

- 既存の ProtectedRoute / GuestRoute が Navigate コンポーネントで適切に実装済み
- `replace` プロパティにより、履歴スタックの汚染を防止
- React Router v7 の API と互換性あり

### 確認事項

- `react-router-dom` バージョン: 7.11.0（package.json より確認済み）
- `BrowserRouter` で SPA ルーティング構成済み
- 保護ページは全て `ProtectedRoute` でラップ済み

## 6. 未解決事項

なし。Technical Context に NEEDS CLARIFICATION はなく、全ての技術選定が確定。

## 参考リンク

- [Zustand 公式ドキュメント](https://github.com/pmndrs/zustand)
- [React Router v7 ドキュメント](https://reactrouter.com/)
- [Testing Library Recipes](https://testing-library.com/docs/react-testing-library/intro/)
- [Playwright Best Practices](https://playwright.dev/docs/best-practices)
