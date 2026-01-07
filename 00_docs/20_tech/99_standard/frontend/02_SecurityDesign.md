# フロントエンド セキュリティ設計標準

## 概要

本ドキュメントは、React + TypeScript フロントエンドにおけるセキュリティ対策の設計標準を定義する。OWASP Top 10 および一般的な Web セキュリティのベストプラクティスに準拠する。

---

## セキュリティ対策一覧

| 脅威 | 対策 | 重要度 |
|------|------|--------|
| XSS（クロスサイトスクリプティング） | React の自動エスケープ、dangerouslySetInnerHTML 禁止 | 高 |
| CSRF（クロスサイトリクエストフォージェリ） | SameSite Cookie、CSRF トークン | 高 |
| 認証情報の漏洩 | セキュアなトークン管理、HttpOnly Cookie | 高 |
| 機密情報の露出 | 環境変数管理、ソースマップ無効化 | 中 |
| 依存関係の脆弱性 | 定期的な依存パッケージ更新 | 中 |
| クリックジャッキング | X-Frame-Options（サーバー側） | 中 |

---

## 1. XSS（クロスサイトスクリプティング）対策

### 1.1 React の自動エスケープ

React は JSX 内の値を自動的にエスケープするため、基本的な XSS 対策は組み込まれている。

```typescript
// ✅ 安全：自動エスケープされる
const UserName = ({ name }: { name: string }) => {
  return <span>{name}</span>; // "<script>" は "&lt;script&gt;" に変換される
};
```

### 1.2 dangerouslySetInnerHTML の禁止

原則として `dangerouslySetInnerHTML` の使用を禁止する。

```typescript
// ❌ 禁止：XSS の脆弱性
const UnsafeComponent = ({ html }: { html: string }) => {
  return <div dangerouslySetInnerHTML={{ __html: html }} />;
};

// ✅ 安全：サニタイズライブラリを使用（やむを得ない場合のみ）
import DOMPurify from 'dompurify';

const SafeHtmlComponent = ({ html }: { html: string }) => {
  const sanitizedHtml = DOMPurify.sanitize(html);
  return <div dangerouslySetInnerHTML={{ __html: sanitizedHtml }} />;
};
```

### 1.3 URL の検証

ユーザー入力由来の URL は必ず検証する。

```typescript
// lib/utils/url.ts

/**
 * URL が安全なプロトコルかどうかを検証
 */
export const isSafeUrl = (url: string): boolean => {
  try {
    const parsed = new URL(url);
    return ['http:', 'https:'].includes(parsed.protocol);
  } catch {
    return false;
  }
};

/**
 * 安全な URL のみを返す（不正な場合は代替値を返す）
 */
export const sanitizeUrl = (url: string, fallback = '#'): string => {
  return isSafeUrl(url) ? url : fallback;
};
```

```typescript
// 使用例
const ExternalLink = ({ href, children }: { href: string; children: ReactNode }) => {
  const safeHref = sanitizeUrl(href);

  return (
    <a href={safeHref} target="_blank" rel="noopener noreferrer">
      {children}
    </a>
  );
};
```

### 1.4 ESLint ルール

XSS を防ぐための ESLint ルールを設定する。

```javascript
// .eslintrc.js
module.exports = {
  rules: {
    'react/no-danger': 'error',
    'react/no-danger-with-children': 'error',
  },
};
```

---

## 2. 認証・認可

### 2.1 トークン管理

#### 推奨：HttpOnly Cookie（サーバー側で設定）

最も安全な方法は、認証トークンを HttpOnly Cookie で管理すること。

```typescript
// バックエンド側で設定（Laravel Sanctum）
// Cookie は JavaScript からアクセスできないため、XSS による盗難を防止
```

#### 代替：メモリ + Refresh Token

SPA でトークンを管理する場合の推奨パターン：

```typescript
// features/auth/stores/authStore.ts
import { create } from 'zustand';

interface AuthState {
  accessToken: string | null;
  setAccessToken: (token: string | null) => void;
}

/**
 * アクセストークンはメモリ（Zustand）で管理
 * - localStorage/sessionStorage に保存しない
 * - ページリロード時は Refresh Token で再取得
 */
export const useAuthStore = create<AuthState>((set) => ({
  accessToken: null,
  setAccessToken: (token) => set({ accessToken: token }),
}));
```

### 2.2 認証状態の確認

```typescript
// features/auth/hooks/useAuth.ts
import { useCallback, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { authApi } from '../services/authApi';
import { useAuthStore } from '../stores/authStore';

/**
 * 認証に関するカスタムフック
 */
export const useAuth = () => {
  const { accessToken, setAccessToken } = useAuthStore();
  const queryClient = useQueryClient();

  // 現在のユーザー情報を取得
  const { data: user, isLoading } = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: authApi.me,
    enabled: !!accessToken,
    retry: false,
  });

  // ログイン処理
  const loginMutation = useMutation({
    mutationFn: authApi.login,
    onSuccess: (data) => {
      setAccessToken(data.accessToken);
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] });
    },
  });

  // ログアウト処理
  const logoutMutation = useMutation({
    mutationFn: authApi.logout,
    onSuccess: () => {
      setAccessToken(null);
      queryClient.clear();
    },
  });

  return {
    user,
    isLoading,
    isAuthenticated: !!user,
    login: loginMutation.mutate,
    logout: logoutMutation.mutate,
  };
};
```

### 2.3 認可（権限チェック）

```typescript
// features/auth/components/PermissionGuard.tsx
import { ReactNode } from 'react';
import { useAuth } from '../hooks/useAuth';

interface Props {
  permission: string;
  children: ReactNode;
  fallback?: ReactNode;
}

/**
 * 権限に基づいてコンテンツを表示/非表示にするコンポーネント
 */
export const PermissionGuard = ({ permission, children, fallback = null }: Props) => {
  const { user } = useAuth();

  if (!user?.permissions?.includes(permission)) {
    return <>{fallback}</>;
  }

  return <>{children}</>;
};

// 使用例
const AdminPanel = () => {
  return (
    <PermissionGuard permission="admin:access" fallback={<AccessDenied />}>
      <AdminDashboard />
    </PermissionGuard>
  );
};
```

---

## 3. CSRF 対策

### 3.1 SameSite Cookie

Laravel Sanctum を使用する場合、CSRF 対策は以下の仕組みで実現される：

```typescript
// lib/api/client.ts
import axios from 'axios';

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true, // Cookie を送信するために必要
});

/**
 * CSRF Cookie を取得（SPA 認証の初期化）
 */
export const initializeCsrf = async () => {
  await apiClient.get('/sanctum/csrf-cookie');
};
```

### 3.2 API リクエスト時の CSRF トークン

```typescript
// lib/api/client.ts
apiClient.interceptors.request.use((config) => {
  // Laravel は XSRF-TOKEN Cookie を自動的に設定
  // Axios は自動的にこの Cookie を X-XSRF-TOKEN ヘッダーとして送信
  return config;
});
```

---

## 4. 機密情報の保護

### 4.1 環境変数の管理

```typescript
// vite.config.ts
export default defineConfig({
  // VITE_ プレフィックスの環境変数のみクライアントに公開される
  envPrefix: 'VITE_',
});
```

```bash
# .env
# ✅ クライアントに公開される
VITE_API_BASE_URL=https://api.example.com

# ❌ クライアントに公開されない（サーバー専用）
DATABASE_URL=postgresql://...
SECRET_KEY=...
```

```typescript
// ❌ 禁止：機密情報をクライアントコードに含めない
const apiKey = 'sk-1234567890'; // ハードコード禁止

// ✅ 推奨：環境変数を使用
const apiUrl = import.meta.env.VITE_API_BASE_URL;
```

### 4.2 本番ビルド設定

```typescript
// vite.config.ts
export default defineConfig({
  build: {
    // 本番環境ではソースマップを無効化
    sourcemap: false,
  },
});
```

### 4.3 機密情報のログ出力禁止

```typescript
// ❌ 禁止：認証情報のログ出力
console.log('Token:', accessToken);
console.log('User data:', user);

// ✅ 推奨：開発環境のみ、機密情報を除外してログ出力
if (import.meta.env.DEV) {
  console.log('User ID:', user.id);
}
```

---

## 5. 入力バリデーション

### 5.1 クライアント側バリデーション

クライアント側バリデーションは UX 向上のためであり、セキュリティ対策ではない。
サーバー側バリデーションが必須。

```typescript
// features/books/schemas/bookSchema.ts
import { z } from 'zod';

/**
 * 書籍作成フォームのバリデーションスキーマ
 */
export const createBookSchema = z.object({
  title: z
    .string()
    .min(1, 'タイトルは必須です')
    .max(255, 'タイトルは255文字以内で入力してください'),
  isbn: z
    .string()
    .regex(/^[0-9-]+$/, 'ISBN は数字とハイフンのみ使用できます')
    .min(10, 'ISBN は10文字以上で入力してください'),
  author: z
    .string()
    .min(1, '著者名は必須です'),
  publishedAt: z
    .string()
    .regex(/^\d{4}-\d{2}-\d{2}$/, '日付は YYYY-MM-DD 形式で入力してください'),
});

export type CreateBookInput = z.infer<typeof createBookSchema>;
```

### 5.2 フォームでの使用

```typescript
// features/books/components/BookForm.tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { createBookSchema, CreateBookInput } from '../schemas/bookSchema';

export const BookForm = () => {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<CreateBookInput>({
    resolver: zodResolver(createBookSchema),
  });

  const onSubmit = async (data: CreateBookInput) => {
    // サーバーに送信
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input {...register('title')} />
      {errors.title && <span>{errors.title.message}</span>}
      {/* ... */}
    </form>
  );
};
```

---

## 6. 依存関係のセキュリティ

### 6.1 脆弱性スキャン

```bash
# npm audit で脆弱性をチェック
npm audit

# 自動修正可能な脆弱性を修正
npm audit fix
```

### 6.2 CI での脆弱性チェック

```yaml
# .github/workflows/security.yml
name: Security Check

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  schedule:
    - cron: '0 0 * * 1' # 毎週月曜日に実行

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci
      - run: npm audit --audit-level=high
```

### 6.3 依存パッケージの更新ポリシー

| 種類 | 更新頻度 | 確認事項 |
|------|----------|----------|
| セキュリティパッチ | 即座 | CHANGELOG 確認 |
| マイナーバージョン | 週次 | テスト実行 |
| メジャーバージョン | 月次 | 破壊的変更の確認 |

---

## 7. 通信セキュリティ

### 7.1 HTTPS 必須

本番環境では HTTPS 通信を必須とする。

```typescript
// lib/api/client.ts
const baseURL = import.meta.env.VITE_API_BASE_URL;

// 本番環境で HTTP を検出した場合は警告
if (import.meta.env.PROD && baseURL.startsWith('http://')) {
  console.error('警告: 本番環境では HTTPS を使用してください');
}
```

### 7.2 外部リンクのセキュリティ

```typescript
// components/ui/ExternalLink.tsx
interface Props {
  href: string;
  children: ReactNode;
}

/**
 * 外部リンク用コンポーネント
 * - rel="noopener noreferrer" で Tabnabbing 攻撃を防止
 */
export const ExternalLink = ({ href, children }: Props) => {
  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
    >
      {children}
    </a>
  );
};
```

---

## 8. エラーハンドリング

### 8.1 エラー情報の秘匿

```typescript
// lib/api/errorHandler.ts

/**
 * ユーザーに表示するエラーメッセージ
 * 技術的な詳細は含めない
 */
export const getUserFriendlyMessage = (error: unknown): string => {
  // 技術的な詳細はログに出力
  console.error('API Error:', error);

  // ユーザーには汎用的なメッセージを表示
  return '処理中にエラーが発生しました。しばらく経ってから再度お試しください。';
};
```

### 8.2 本番環境でのエラー表示

```typescript
// components/feedback/ErrorBoundary/index.tsx
export class ErrorBoundary extends Component<Props, State> {
  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    // 本番環境ではエラー監視サービスに送信
    if (import.meta.env.PROD) {
      // Sentry などに送信
      // Sentry.captureException(error);
    }
  }

  render() {
    if (this.state.hasError) {
      // 本番環境では詳細なエラー情報を表示しない
      return import.meta.env.PROD
        ? <GenericErrorPage />
        : <DetailedErrorPage error={this.state.error} />;
    }

    return this.props.children;
  }
}
```

---

## 9. セキュリティチェックリスト

### 開発時

- [ ] `dangerouslySetInnerHTML` を使用していないこと
- [ ] ユーザー入力由来の URL を検証していること
- [ ] 認証トークンを localStorage に保存していないこと
- [ ] 機密情報をソースコードにハードコードしていないこと
- [ ] Console.log で機密情報を出力していないこと

### リリース前

- [ ] 本番ビルドでソースマップが無効化されていること
- [ ] npm audit で高リスクの脆弱性がないこと
- [ ] 環境変数が正しく設定されていること
- [ ] HTTPS が有効化されていること
- [ ] CSP（Content Security Policy）が設定されていること

### 定期確認

- [ ] 依存パッケージの脆弱性スキャン（週次）
- [ ] セキュリティパッチの適用（随時）
- [ ] アクセスログの監視（日次）

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [04_Non-FunctionalRequirements.md](./04_Non-FunctionalRequirements.md) - 非機能要件
- [バックエンド セキュリティ設計](../backend/03_SecurityDesign.md) - バックエンド側のセキュリティ対策
