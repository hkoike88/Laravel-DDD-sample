# ADR-0004: 状態管理（TanStack Query + Zustand）

## ステータス

採用

## コンテキスト

React アプリケーションにおいて、以下の状態を適切に管理する必要がある。

- **サーバー状態**: API から取得するデータ（書籍一覧、貸出情報など）
- **クライアント状態**: UI の状態（モーダル開閉、認証情報、テーマ設定など）

従来の Redux のような単一ストアで全状態を管理するアプローチは、ボイラープレートが多く、サーバー状態の管理に適していないという課題がある。

## 決定

**サーバー状態** と **クライアント状態** を分離し、それぞれに最適なライブラリを採用する。

- **サーバー状態**: TanStack Query（React Query）5.62
- **クライアント状態**: Zustand 5.0

### 採用理由

#### TanStack Query（サーバー状態）

1. **キャッシュ管理の自動化**
   - API レスポンスを自動的にキャッシュ
   - staleTime、cacheTime による細かな制御

2. **データ同期**
   - バックグラウンドでの自動再フェッチ
   - 楽観的更新（Optimistic Update）のサポート

3. **ローディング・エラー状態の統一**
   - isLoading、isError、data を一貫した形式で取得
   - Suspense との統合

4. **DevTools**
   - React Query DevTools でキャッシュ状態を可視化

#### Zustand（クライアント状態）

1. **シンプルな API**
   - Redux のようなボイラープレートが不要
   - 直感的な状態更新

2. **軽量**
   - バンドルサイズが小さい（約 1KB）
   - パフォーマンスへの影響が最小限

3. **TypeScript サポート**
   - 型推論が優れている
   - 型定義が簡潔

4. **永続化**
   - persist ミドルウェアで localStorage 連携が容易

## 比較検討

### サーバー状態管理

| 項目 | TanStack Query | SWR | Apollo Client | RTK Query |
|------|----------------|-----|---------------|-----------|
| キャッシュ機能 | ◎ | ○ | ◎ | ◎ |
| DevTools | ◎ | △ | ◎ | ○ |
| 学習曲線 | 低 | 低 | 高 | 中 |
| バンドルサイズ | 中 | 小 | 大 | 中 |
| REST API 向け | ◎ | ◎ | △ | ◎ |

### クライアント状態管理

| 項目 | Zustand | Redux Toolkit | Jotai | Recoil |
|------|---------|---------------|-------|--------|
| 学習曲線 | 低 | 中 | 低 | 中 |
| ボイラープレート | 少 | 中 | 少 | 中 |
| バンドルサイズ | 小 | 中 | 小 | 中 |
| DevTools | ○ | ◎ | ○ | ○ |
| 永続化 | ◎ | ○ | ○ | ○ |

### 不採用理由

- **Redux Toolkit**: サーバー状態管理には RTK Query が必要で、TanStack Query と比較するとやや複雑
- **SWR**: TanStack Query より機能が限定的（Mutation のサポートが弱い）
- **Jotai/Recoil**: アトムベースは小規模アプリ向き。学習コストに対する利点が少ない

## 結果

### メリット

- 状態の種類に応じた最適なツールを使用できる
- サーバー状態のキャッシュ管理が自動化され、開発効率向上
- ボイラープレートが少なく、コードがシンプル

### デメリット

- 2つのライブラリを学習する必要がある
- 状態をどちらで管理するか判断が必要

### 状態の分類ガイドライン

| 状態の種類 | 管理方法 | 例 |
|-----------|---------|-----|
| API から取得するデータ | TanStack Query | 書籍一覧、ユーザー情報 |
| 認証情報 | Zustand（永続化） | トークン、ログインユーザー |
| UI 状態（グローバル） | Zustand | サイドバー開閉、テーマ |
| UI 状態（ローカル） | useState | フォーム入力、モーダル |
| URL パラメータ | React Router | 検索条件、ページ番号 |

## 使用例

### TanStack Query

```typescript
// hooks/useBooks.ts
export const useBooks = () => {
  return useQuery({
    queryKey: ['books'],
    queryFn: () => bookApi.getAll(),
    staleTime: 5 * 60 * 1000,
  });
};
```

### Zustand

```typescript
// stores/authStore.ts
export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      login: (user) => set({ user }),
      logout: () => set({ user: null }),
    }),
    { name: 'auth' }
  )
);
```

## 参考資料

- [TanStack Query 公式ドキュメント](https://tanstack.com/query)
- [Zustand 公式ドキュメント](https://zustand-demo.pmnd.rs/)
- [フロントエンド アーキテクチャ設計](../../99_standard/frontend/01_ArchitectureDesign.md)
