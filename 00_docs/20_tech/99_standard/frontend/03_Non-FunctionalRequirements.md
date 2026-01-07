# フロントエンド 非機能要件標準

## 概要

本ドキュメントは、React + TypeScript フロントエンドにおける非機能要件の設計標準を定義する。パフォーマンス、アクセシビリティ、保守性、ユーザビリティなどの品質特性を確保するための指針を示す。

---

## 非機能要件一覧

| カテゴリ | 要件 | 目標値 |
|----------|------|--------|
| パフォーマンス | 初期表示時間（LCP） | 2.5秒以内 |
| パフォーマンス | インタラクション応答（INP） | 200ms以内 |
| パフォーマンス | レイアウト安定性（CLS） | 0.1以下 |
| アクセシビリティ | WCAG 準拠レベル | AA |
| ブラウザ対応 | サポートブラウザ | 最新2バージョン |
| 保守性 | テストカバレッジ | 80%以上 |

---

## 1. パフォーマンス

### 1.1 Core Web Vitals 目標

Google が定める Core Web Vitals を満たすことを目標とする。

| 指標 | 説明 | 目標値 | 測定方法 |
|------|------|--------|----------|
| LCP | Largest Contentful Paint | 2.5秒以内 | Lighthouse |
| INP | Interaction to Next Paint | 200ms以内 | Chrome DevTools |
| CLS | Cumulative Layout Shift | 0.1以下 | Lighthouse |

### 1.2 バンドルサイズ最適化

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    // チャンク分割設定
    rollupOptions: {
      output: {
        manualChunks: {
          // ベンダーライブラリを分割
          vendor: ['react', 'react-dom', 'react-router-dom'],
          query: ['@tanstack/react-query'],
        },
      },
    },
    // チャンクサイズ警告の閾値
    chunkSizeWarningLimit: 500,
  },
});
```

### 1.3 コード分割（Code Splitting）

```typescript
// app/router.tsx
import { lazy, Suspense } from 'react';
import { createBrowserRouter } from 'react-router-dom';
import { Loading } from '@/components/feedback/Loading';

// 遅延読み込み
const BooksPage = lazy(() => import('@/pages/Books'));
const BookDetailPage = lazy(() => import('@/pages/Books/[id]'));
const AdminPage = lazy(() => import('@/pages/Admin'));

export const router = createBrowserRouter([
  {
    path: '/',
    element: <RootLayout />,
    children: [
      {
        path: 'books',
        element: (
          <Suspense fallback={<Loading />}>
            <BooksPage />
          </Suspense>
        ),
      },
      {
        path: 'books/:id',
        element: (
          <Suspense fallback={<Loading />}>
            <BookDetailPage />
          </Suspense>
        ),
      },
      // 管理画面は別チャンクに
      {
        path: 'admin/*',
        element: (
          <Suspense fallback={<Loading />}>
            <AdminPage />
          </Suspense>
        ),
      },
    ],
  },
]);
```

### 1.4 画像最適化

```typescript
// components/ui/Image/index.tsx
interface Props {
  src: string;
  alt: string;
  width: number;
  height: number;
  priority?: boolean;
}

/**
 * 最適化された画像コンポーネント
 */
export const OptimizedImage = ({ src, alt, width, height, priority = false }: Props) => {
  return (
    <img
      src={src}
      alt={alt}
      width={width}
      height={height}
      loading={priority ? 'eager' : 'lazy'}
      decoding="async"
      style={{ aspectRatio: `${width}/${height}` }}
    />
  );
};
```

### 1.5 メモ化による再レンダリング最適化

```typescript
// features/books/components/BookList.tsx
import { memo, useMemo, useCallback } from 'react';

interface BookCardProps {
  book: Book;
  onSelect: (id: string) => void;
}

/**
 * 書籍カードコンポーネント
 * memo で不要な再レンダリングを防止
 */
export const BookCard = memo(({ book, onSelect }: BookCardProps) => {
  const handleClick = useCallback(() => {
    onSelect(book.id);
  }, [book.id, onSelect]);

  return (
    <div onClick={handleClick}>
      <h3>{book.title}</h3>
      <p>{book.author}</p>
    </div>
  );
});

BookCard.displayName = 'BookCard';
```

### 1.6 パフォーマンス測定

```typescript
// lib/performance/metrics.ts

/**
 * Web Vitals を測定してレポート
 */
export const reportWebVitals = async () => {
  const { onCLS, onINP, onLCP } = await import('web-vitals');

  onCLS((metric) => {
    console.log('CLS:', metric.value);
    // 分析サービスに送信
  });

  onINP((metric) => {
    console.log('INP:', metric.value);
  });

  onLCP((metric) => {
    console.log('LCP:', metric.value);
  });
};
```

---

## 2. アクセシビリティ（A11y）

### 2.1 WCAG 2.1 AA 準拠

Web Content Accessibility Guidelines（WCAG）2.1 レベル AA に準拠する。

| 原則 | 要件 | 実装 |
|------|------|------|
| 知覚可能 | 代替テキスト | img タグに alt 属性 |
| 操作可能 | キーボード操作 | フォーカス管理 |
| 理解可能 | エラー識別 | aria-describedby |
| 堅牢 | 支援技術との互換性 | セマンティック HTML |

### 2.2 セマンティック HTML

```typescript
// ❌ 非推奨：div の乱用
const BadNavigation = () => (
  <div className="nav">
    <div onClick={handleClick}>ホーム</div>
    <div onClick={handleClick}>書籍一覧</div>
  </div>
);

// ✅ 推奨：セマンティック HTML
const GoodNavigation = () => (
  <nav aria-label="メインナビゲーション">
    <ul>
      <li><a href="/">ホーム</a></li>
      <li><a href="/books">書籍一覧</a></li>
    </ul>
  </nav>
);
```

### 2.3 ARIA 属性

```typescript
// components/ui/Modal/index.tsx
interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
}

/**
 * アクセシブルなモーダルコンポーネント
 */
export const Modal = ({ isOpen, onClose, title, children }: ModalProps) => {
  const titleId = useId();

  if (!isOpen) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby={titleId}
    >
      <h2 id={titleId}>{title}</h2>
      <div>{children}</div>
      <button onClick={onClose} aria-label="閉じる">
        ×
      </button>
    </div>
  );
};
```

### 2.4 フォーカス管理

```typescript
// hooks/useFocusTrap.ts
import { useEffect, useRef } from 'react';

/**
 * フォーカストラップを実装するカスタムフック
 */
export const useFocusTrap = <T extends HTMLElement>(isActive: boolean) => {
  const ref = useRef<T>(null);

  useEffect(() => {
    if (!isActive || !ref.current) return;

    const element = ref.current;
    const focusableElements = element.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0] as HTMLElement;
    const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key !== 'Tab') return;

      if (e.shiftKey && document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
      } else if (!e.shiftKey && document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
      }
    };

    element.addEventListener('keydown', handleKeyDown);
    firstElement?.focus();

    return () => {
      element.removeEventListener('keydown', handleKeyDown);
    };
  }, [isActive]);

  return ref;
};
```

### 2.5 カラーコントラスト

```css
/* styles/globals.css */

:root {
  /* WCAG AA 準拠のカラーパレット */
  /* コントラスト比 4.5:1 以上を確保 */
  --color-text-primary: #1a1a1a;      /* 背景白に対して 16:1 */
  --color-text-secondary: #525252;    /* 背景白に対して 7.5:1 */
  --color-text-disabled: #737373;     /* 背景白に対して 4.5:1 */

  /* エラー・成功色もコントラストを確保 */
  --color-error: #dc2626;             /* コントラスト比 5.9:1 */
  --color-success: #16a34a;           /* コントラスト比 4.5:1 */
}
```

### 2.6 アクセシビリティテスト

```typescript
// vitest.config.ts
import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    setupFiles: ['./src/test/setup.ts'],
  },
});
```

```typescript
// src/test/setup.ts
import '@testing-library/jest-dom';
import { toHaveNoViolations } from 'jest-axe';

expect.extend(toHaveNoViolations);
```

```typescript
// features/books/components/BookCard.test.tsx
import { render } from '@testing-library/react';
import { axe } from 'jest-axe';
import { BookCard } from './BookCard';

describe('BookCard', () => {
  it('アクセシビリティ違反がないこと', async () => {
    const { container } = render(
      <BookCard book={mockBook} onSelect={jest.fn()} />
    );

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
```

---

## 3. ブラウザ対応

### 3.1 サポートブラウザ

| ブラウザ | バージョン | サポート |
|----------|-----------|----------|
| Chrome | 最新2バージョン | ✅ |
| Firefox | 最新2バージョン | ✅ |
| Safari | 最新2バージョン | ✅ |
| Edge | 最新2バージョン | ✅ |
| IE | 全バージョン | ❌ |

### 3.2 Browserslist 設定

```json
// package.json
{
  "browserslist": [
    "> 1%",
    "last 2 versions",
    "not dead",
    "not ie <= 11"
  ]
}
```

### 3.3 ポリフィル

```typescript
// src/main.tsx
import 'core-js/stable';
import 'regenerator-runtime/runtime';

// 必要に応じて個別のポリフィルを追加
// import 'whatwg-fetch'; // fetch API
// import 'intersection-observer'; // IntersectionObserver
```

---

## 4. レスポンシブデザイン

### 4.1 ブレークポイント

| 名称 | 幅 | 対象デバイス |
|------|-----|--------------|
| sm | 640px | スマートフォン |
| md | 768px | タブレット |
| lg | 1024px | ノート PC |
| xl | 1280px | デスクトップ |
| 2xl | 1536px | ワイドスクリーン |

### 4.2 Tailwind CSS 設定

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    screens: {
      'sm': '640px',
      'md': '768px',
      'lg': '1024px',
      'xl': '1280px',
      '2xl': '1536px',
    },
  },
};
```

### 4.3 レスポンシブコンポーネント

```typescript
// features/books/components/BookList.tsx

/**
 * レスポンシブな書籍一覧
 * - モバイル: 1列
 * - タブレット: 2列
 * - デスクトップ: 3列
 */
export const BookList = ({ books }: { books: Book[] }) => {
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

## 5. 国際化（i18n）対応

### 5.1 対応言語

| 言語 | コード | 優先度 |
|------|--------|--------|
| 日本語 | ja | 高（デフォルト） |
| 英語 | en | 中（将来対応） |

### 5.2 日付・数値フォーマット

```typescript
// lib/utils/format.ts

/**
 * 日付をローカライズしてフォーマット
 */
export const formatDate = (date: Date | string, locale = 'ja-JP'): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  return d.toLocaleDateString(locale, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

/**
 * 数値をローカライズしてフォーマット
 */
export const formatNumber = (value: number, locale = 'ja-JP'): string => {
  return new Intl.NumberFormat(locale).format(value);
};

/**
 * 通貨をローカライズしてフォーマット
 */
export const formatCurrency = (
  value: number,
  currency = 'JPY',
  locale = 'ja-JP'
): string => {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  }).format(value);
};
```

---

## 6. エラーハンドリング・復旧性

### 6.1 エラー境界

```typescript
// components/feedback/ErrorBoundary/index.tsx
import { Component, ErrorInfo, ReactNode } from 'react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface State {
  hasError: boolean;
}

/**
 * エラー境界コンポーネント
 * 子コンポーネントのエラーをキャッチしてフォールバック UI を表示
 */
export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false };

  static getDerivedStateFromError(): State {
    return { hasError: true };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    this.props.onError?.(error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback ?? <DefaultErrorFallback />;
    }

    return this.props.children;
  }
}
```

### 6.2 オフライン対応

```typescript
// hooks/useOnlineStatus.ts
import { useSyncExternalStore } from 'react';

/**
 * オンライン状態を監視するカスタムフック
 */
export const useOnlineStatus = () => {
  return useSyncExternalStore(
    (callback) => {
      window.addEventListener('online', callback);
      window.addEventListener('offline', callback);
      return () => {
        window.removeEventListener('online', callback);
        window.removeEventListener('offline', callback);
      };
    },
    () => navigator.onLine
  );
};
```

```typescript
// components/feedback/OfflineNotice/index.tsx
import { useOnlineStatus } from '@/hooks/useOnlineStatus';

/**
 * オフライン時の通知コンポーネント
 */
export const OfflineNotice = () => {
  const isOnline = useOnlineStatus();

  if (isOnline) return null;

  return (
    <div className="fixed bottom-4 right-4 bg-yellow-500 text-white px-4 py-2 rounded">
      インターネット接続がありません
    </div>
  );
};
```

---

## 7. 保守性

### 7.1 テストカバレッジ目標

| 種類 | 対象 | 目標カバレッジ |
|------|------|---------------|
| Unit | Hooks / Utils | 90%+ |
| Component | UI コンポーネント | 80%+ |
| Integration | Feature | 70%+ |
| E2E | 主要ユーザーフロー | 主要フロー網羅 |

### 7.2 テスト構成

```
tests/
├── unit/                    # ユニットテスト
│   ├── hooks/
│   └── utils/
├── components/              # コンポーネントテスト
│   └── features/
├── integration/             # 統合テスト
│   └── api/
└── e2e/                     # E2E テスト
    └── scenarios/
```

### 7.3 Storybook によるコンポーネントカタログ

```typescript
// components/ui/Button/Button.stories.tsx
import type { Meta, StoryObj } from '@storybook/react';
import { Button } from './index';

const meta: Meta<typeof Button> = {
  title: 'UI/Button',
  component: Button,
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof Button>;

export const Primary: Story = {
  args: {
    variant: 'primary',
    children: 'ボタン',
  },
};

export const Secondary: Story = {
  args: {
    variant: 'secondary',
    children: 'ボタン',
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
    children: 'ボタン',
  },
};
```

---

## 8. 監視・ログ

### 8.1 エラー監視

```typescript
// lib/monitoring/errorReporter.ts

interface ErrorReport {
  message: string;
  stack?: string;
  componentStack?: string;
  url: string;
  userAgent: string;
  timestamp: string;
}

/**
 * エラーを監視サービスに報告
 */
export const reportError = (error: Error, componentStack?: string) => {
  const report: ErrorReport = {
    message: error.message,
    stack: error.stack,
    componentStack,
    url: window.location.href,
    userAgent: navigator.userAgent,
    timestamp: new Date().toISOString(),
  };

  // 本番環境のみ送信
  if (import.meta.env.PROD) {
    // Sentry などのエラー監視サービスに送信
    console.error('Error reported:', report);
  }
};
```

### 8.2 パフォーマンス監視

```typescript
// lib/monitoring/performanceReporter.ts

/**
 * パフォーマンスメトリクスを報告
 */
export const reportPerformance = () => {
  if (!('performance' in window)) return;

  const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;

  const metrics = {
    // DNS 解決時間
    dns: navigation.domainLookupEnd - navigation.domainLookupStart,
    // TCP 接続時間
    tcp: navigation.connectEnd - navigation.connectStart,
    // リクエスト時間
    request: navigation.responseStart - navigation.requestStart,
    // レスポンス時間
    response: navigation.responseEnd - navigation.responseStart,
    // DOM 構築時間
    dom: navigation.domContentLoadedEventEnd - navigation.responseEnd,
    // ページ読み込み完了時間
    load: navigation.loadEventEnd - navigation.navigationStart,
  };

  console.log('Performance metrics:', metrics);
};
```

---

## 9. 非機能要件チェックリスト

### パフォーマンス

- [ ] LCP が 2.5 秒以内であること
- [ ] INP が 200ms 以内であること
- [ ] CLS が 0.1 以下であること
- [ ] バンドルサイズが適切に分割されていること
- [ ] 画像が最適化されていること

### アクセシビリティ

- [ ] WCAG 2.1 AA に準拠していること
- [ ] キーボード操作が可能であること
- [ ] スクリーンリーダーで利用可能であること
- [ ] カラーコントラストが十分であること

### ブラウザ対応

- [ ] サポートブラウザで動作確認済みであること
- [ ] レスポンシブデザインが実装されていること

### 保守性

- [ ] テストカバレッジが目標値以上であること
- [ ] Storybook でコンポーネントがドキュメント化されていること
- [ ] ESLint / Prettier のエラーがないこと

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計
- [バックエンド 非機能要件](../backend/04_Non-FunctionalRequirements.md) - バックエンド側の非機能要件
