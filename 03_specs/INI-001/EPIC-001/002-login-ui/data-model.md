# Data Model: ログイン画面実装

**Feature**: 003-login-ui
**Date**: 2025-12-25

## 概要

ログイン画面はフロントエンドのみの実装であり、永続化データは持たない。以下はフロントエンドで扱う型定義とステート構造を定義する。

## フロントエンド型定義

### ログインフォーム

```typescript
// 入力データ
interface LoginFormData {
  email: string;      // メールアドレス（必須、メール形式）
  password: string;   // パスワード（必須）
}

// バリデーションルール
// - email: 必須、メール形式（RFC 5322）
// - password: 必須、最小8文字（バックエンド側で検証）
```

### API レスポンス

```typescript
// ログイン成功時のレスポンス
interface StaffResponse {
  data: {
    id: string;       // ULID形式（26文字）
    name: string;     // 職員名
    email: string;    // メールアドレス
  };
}

// エラーレスポンス
interface ErrorResponse {
  message: string;    // エラーメッセージ
}

// バリデーションエラーレスポンス
interface ValidationErrorResponse {
  message: string;
  errors: Record<string, string[]>;
}
```

### 認証ステート

```typescript
// Zustand ストア
interface AuthState {
  // ステート
  isAuthenticated: boolean;
  currentUser: StaffResponse['data'] | null;
  isLoading: boolean;

  // アクション
  setAuthenticated: (user: StaffResponse['data']) => void;
  clearAuthentication: () => void;
  setLoading: (loading: boolean) => void;
}

// 初期状態
const initialState = {
  isAuthenticated: false,
  currentUser: null,
  isLoading: true,  // 初回認証確認中は true
};
```

### UI ステート

```typescript
// ログインフォームのローカルステート
interface LoginFormState {
  isSubmitting: boolean;     // 送信中フラグ
  apiError: string | null;   // API エラーメッセージ
}
```

## ステート遷移

### 認証ステート

```
[未認証] ──(ログイン成功)──> [認証済み]
   │                            │
   │<──(ログアウト/セッション切れ)──┘
   │
   └──(認証確認API成功)──> [認証済み]
```

### ログインフォームステート

```
[初期表示] ──(送信)──> [送信中]
    │                    │
    │<──(成功)───────────┘ → [リダイレクト]
    │                    │
    │<──(失敗)───────────┘ → [エラー表示]
    │
    └──(入力変更)──> [バリデーション実行]
                         │
                         └──(エラーあり)──> [エラー表示]
```

## 依存関係

### バックエンド API（ST-002 で実装済み）

| エンドポイント | メソッド | 用途 |
|---------------|---------|------|
| /sanctum/csrf-cookie | GET | CSRF トークン取得 |
| /api/auth/login | POST | ログイン |
| /api/auth/logout | POST | ログアウト |
| /api/auth/user | GET | 認証ユーザー取得 |

### ブラウザストレージ

| 種別 | キー | 用途 |
|-----|-----|------|
| Cookie | laravel_session | セッション管理（バックエンド設定） |
| Cookie | XSRF-TOKEN | CSRF トークン（バックエンド設定） |

※ フロントエンドでは Cookie を直接操作せず、Axios の `withCredentials` で自動処理
