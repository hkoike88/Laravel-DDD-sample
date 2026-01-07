# フロントエンド アーキテクチャ設計標準

## 概要

本プロジェクトのフロントエンドは、**Feature-based アーキテクチャ** を採用し、React + TypeScript による型安全な SPA を構築する。

---

## 採用技術スタック

| 項目 | 技術 | バージョン | 用途 |
|------|------|-----------|------|
| 言語 | TypeScript | 5.x | 型安全な開発 |
| UI ライブラリ | React | 18.x | コンポーネントベース UI |
| ビルドツール | Vite | 5.x | 高速ビルド・HMR |
| 状態管理（サーバー） | TanStack Query | 5.x | API データキャッシュ |
| 状態管理（クライアント） | Zustand | 4.x | グローバル状態管理 |
| ルーティング | React Router | 6.x | SPA ルーティング |
| HTTP クライアント | Axios | 1.x | API 通信 |
| フォーム | React Hook Form | 7.x | フォームバリデーション |
| バリデーション | Zod | 3.x | スキーマバリデーション |
| スタイリング | Tailwind CSS | 3.x | ユーティリティファースト CSS |

---

## アーキテクチャ方針

### 採用理由

| 観点 | Feature-based アーキテクチャの利点 |
|------|-----------------------------------|
| 保守性 | 機能単位でコードが集約され、変更の影響範囲が局所化 |
| スケーラビリティ | 新機能追加時に既存コードへの影響が最小限 |
| チーム開発 | 機能単位での分担開発が容易 |
| テスト容易性 | 機能単位でのテストが書きやすい |
| 再利用性 | 共通コンポーネントの明確な分離 |

---

## レイヤー構成

```
┌─────────────────────────────────────────────────────┐
│                      Pages                          │
│              (ルーティング・レイアウト)               │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                     Features                        │
│              (機能単位のコンポーネント)               │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                      Hooks                          │
│              (ビジネスロジック・状態管理)             │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                     Services                        │
│                   (API 通信層)                      │
└─────────────────────────────────────────────────────┘
```

### 依存関係ルール

```
[Pages] → [Features] → [Hooks] → [Services]
                ↓
           [Components]（共通 UI コンポーネント）
```

| レイヤー | 責務 | 禁止事項 |
|----------|------|----------|
| Pages | ルーティング・レイアウト構成 | ビジネスロジックの実装禁止 |
| Features | 機能単位の UI・状態管理 | 他機能への直接依存禁止 |
| Hooks | ビジネスロジック・API 呼び出し | UI 要素の返却禁止 |
| Services | HTTP 通信・データ変換 | 状態管理の実装禁止 |
| Components | 汎用 UI コンポーネント | ビジネスロジックの実装禁止 |

---

## ディレクトリ構成

```
src/
├── app/                      # アプリケーション設定
│   ├── App.tsx               # ルートコンポーネント
│   ├── router.tsx            # ルーティング設定
│   └── providers/            # Context Provider
│       ├── QueryProvider.tsx
│       └── AuthProvider.tsx
│
├── pages/                    # ページコンポーネント
│   ├── Home/
│   │   └── index.tsx
│   ├── Books/
│   │   ├── index.tsx         # 一覧ページ
│   │   ├── [id]/             # 詳細ページ
│   │   └── new/              # 新規作成ページ
│   └── Auth/
│       ├── Login/
│       └── Register/
│
├── features/                 # 機能モジュール
│   ├── books/                # 書籍機能
│   │   ├── components/       # 機能固有コンポーネント
│   │   │   ├── BookList.tsx
│   │   │   ├── BookCard.tsx
│   │   │   └── BookForm.tsx
│   │   ├── hooks/            # 機能固有 Hooks
│   │   │   ├── useBooks.ts
│   │   │   └── useBookMutation.ts
│   │   ├── services/         # API 通信
│   │   │   └── bookApi.ts
│   │   ├── types/            # 型定義
│   │   │   └── book.ts
│   │   └── index.ts          # 公開 API
│   │
│   ├── auth/                 # 認証機能
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── services/
│   │   └── stores/           # Zustand ストア
│   │
│   └── loans/                # 貸出機能
│       └── ...
│
├── components/               # 共通 UI コンポーネント
│   ├── ui/                   # 基本 UI 要素
│   │   ├── Button/
│   │   ├── Input/
│   │   ├── Modal/
│   │   └── Table/
│   ├── layout/               # レイアウト
│   │   ├── Header/
│   │   ├── Sidebar/
│   │   └── Footer/
│   └── feedback/             # フィードバック
│       ├── Loading/
│       ├── ErrorBoundary/
│       └── Toast/
│
├── hooks/                    # 共通 Hooks
│   ├── useDebounce.ts
│   ├── useLocalStorage.ts
│   └── useMediaQuery.ts
│
├── lib/                      # ユーティリティ
│   ├── api/                  # API クライアント設定
│   │   ├── client.ts
│   │   └── interceptors.ts
│   ├── utils/                # ヘルパー関数
│   │   ├── date.ts
│   │   └── format.ts
│   └── constants/            # 定数
│       └── routes.ts
│
├── types/                    # グローバル型定義
│   ├── api.ts
│   └── common.ts
│
└── styles/                   # グローバルスタイル
    └── globals.css
```

---

## 状態管理設計

### サーバー状態（TanStack Query）

API から取得するデータはすべて TanStack Query で管理する。

```typescript
// features/books/hooks/useBooks.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { bookApi } from '../services/bookApi';
import type { Book } from '../types/book';

/**
 * 書籍一覧を取得するカスタムフック
 */
export const useBooks = () => {
  return useQuery({
    queryKey: ['books'],
    queryFn: bookApi.getAll,
    staleTime: 5 * 60 * 1000, // 5分間キャッシュ
  });
};

/**
 * 書籍を作成するカスタムフック
 */
export const useCreateBook = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: bookApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books'] });
    },
  });
};
```

### クライアント状態（Zustand）

UI 状態やセッション情報は Zustand で管理する。

```typescript
// features/auth/stores/authStore.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  login: (user: User, token: string) => void;
  logout: () => void;
}

/**
 * 認証状態を管理するストア
 */
export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      login: (user, token) =>
        set({ user, token, isAuthenticated: true }),
      logout: () =>
        set({ user: null, token: null, isAuthenticated: false }),
    }),
    { name: 'auth-storage' }
  )
);
```

---

## API 通信設計

### API クライアント

```typescript
// lib/api/client.ts
import axios from 'axios';
import { useAuthStore } from '@/features/auth/stores/authStore';

/**
 * Axios インスタンスの設定
 */
export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// リクエストインターセプター
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// レスポンスインターセプター
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

### サービス層

```typescript
// features/books/services/bookApi.ts
import { apiClient } from '@/lib/api/client';
import type { Book, CreateBookDTO, UpdateBookDTO } from '../types/book';

/**
 * 書籍 API サービス
 */
export const bookApi = {
  /**
   * 書籍一覧を取得
   */
  getAll: async (): Promise<Book[]> => {
    const { data } = await apiClient.get('/api/books');
    return data;
  },

  /**
   * 書籍詳細を取得
   */
  getById: async (id: string): Promise<Book> => {
    const { data } = await apiClient.get(`/api/books/${id}`);
    return data;
  },

  /**
   * 書籍を作成
   */
  create: async (dto: CreateBookDTO): Promise<Book> => {
    const { data } = await apiClient.post('/api/books', dto);
    return data;
  },

  /**
   * 書籍を更新
   */
  update: async (id: string, dto: UpdateBookDTO): Promise<Book> => {
    const { data } = await apiClient.put(`/api/books/${id}`, dto);
    return data;
  },

  /**
   * 書籍を削除
   */
  delete: async (id: string): Promise<void> => {
    await apiClient.delete(`/api/books/${id}`);
  },
};
```

---

## ルーティング設計

### ルート定義

```typescript
// app/router.tsx
import { createBrowserRouter, Navigate } from 'react-router-dom';
import { AuthGuard } from '@/features/auth/components/AuthGuard';

export const router = createBrowserRouter([
  {
    path: '/',
    element: <RootLayout />,
    errorElement: <ErrorPage />,
    children: [
      // 公開ルート
      { path: 'login', element: <LoginPage /> },
      { path: 'register', element: <RegisterPage /> },

      // 認証必須ルート
      {
        element: <AuthGuard />,
        children: [
          { index: true, element: <HomePage /> },
          { path: 'books', element: <BooksPage /> },
          { path: 'books/:id', element: <BookDetailPage /> },
          { path: 'books/new', element: <BookCreatePage /> },
          { path: 'loans', element: <LoansPage /> },
        ],
      },
    ],
  },
]);
```

### 認証ガード

```typescript
// features/auth/components/AuthGuard.tsx
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';

/**
 * 認証が必要なルートを保護するコンポーネント
 */
export const AuthGuard = () => {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const location = useLocation();

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return <Outlet />;
};
```

---

## コンポーネント設計パターン

### Container / Presentational パターン

```typescript
// features/books/components/BookList.tsx (Container)
import { useBooks } from '../hooks/useBooks';
import { BookListPresenter } from './BookListPresenter';

/**
 * 書籍一覧のコンテナコンポーネント
 * データ取得とロジックを担当
 */
export const BookList = () => {
  const { data: books, isLoading, error } = useBooks();

  if (isLoading) return <Loading />;
  if (error) return <Error message={error.message} />;

  return <BookListPresenter books={books ?? []} />;
};
```

```typescript
// features/books/components/BookListPresenter.tsx (Presentational)
import type { Book } from '../types/book';
import { BookCard } from './BookCard';

interface Props {
  books: Book[];
}

/**
 * 書籍一覧の表示コンポーネント
 * UI の表示のみを担当
 */
export const BookListPresenter = ({ books }: Props) => {
  if (books.length === 0) {
    return <p>書籍が登録されていません</p>;
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {books.map((book) => (
        <BookCard key={book.id} book={book} />
      ))}
    </div>
  );
};
```

---

## エラーハンドリング

### ErrorBoundary

```typescript
// components/feedback/ErrorBoundary/index.tsx
import { Component, ErrorInfo, ReactNode } from 'react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error?: Error;
}

/**
 * エラー境界コンポーネント
 */
export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false };

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    // エラー監視サービスへの送信
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback ?? <DefaultErrorFallback />;
    }

    return this.props.children;
  }
}
```

### API エラーハンドリング

```typescript
// lib/api/errorHandler.ts
import { AxiosError } from 'axios';

interface ApiError {
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

/**
 * API エラーをユーザー向けメッセージに変換
 */
export const handleApiError = (error: unknown): string => {
  if (error instanceof AxiosError) {
    const apiError = error.response?.data as ApiError;

    if (apiError?.message) {
      return apiError.message;
    }

    switch (error.response?.status) {
      case 400:
        return '入力内容に誤りがあります';
      case 401:
        return '認証が必要です';
      case 403:
        return 'アクセス権限がありません';
      case 404:
        return 'リソースが見つかりません';
      case 422:
        return 'バリデーションエラーが発生しました';
      case 500:
        return 'サーバーエラーが発生しました';
      default:
        return '通信エラーが発生しました';
    }
  }

  return '予期しないエラーが発生しました';
};
```

---

## 主要コンポーネント一覧

| コンポーネント | 責務 | 配置 |
|---------------|------|------|
| Page | ルート対応・レイアウト | pages/ |
| Feature Component | 機能固有の UI | features/*/components/ |
| UI Component | 汎用 UI 要素 | components/ui/ |
| Layout Component | 共通レイアウト | components/layout/ |
| Custom Hook | ロジックの再利用 | features/*/hooks/ または hooks/ |
| Service | API 通信 | features/*/services/ |
| Store | グローバル状態 | features/*/stores/ |

---

## 開発ルール

### 必須

- [ ] TypeScript の strict モードを有効にすること
- [ ] コンポーネントは関数コンポーネントで実装すること
- [ ] API 通信は TanStack Query を経由すること
- [ ] グローバル状態は Zustand で管理すること
- [ ] Feature 間の直接依存を作らないこと

### 推奨

- [ ] 1 コンポーネント = 1 ファイル
- [ ] コンポーネントのテストカバレッジ 80% 以上
- [ ] カスタム Hook の Unit テストを書くこと
- [ ] Storybook でコンポーネントカタログを作成すること

---

## 関連ドキュメント

- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計
- [04_Non-FunctionalRequirements.md](./04_Non-FunctionalRequirements.md) - 非機能要件
