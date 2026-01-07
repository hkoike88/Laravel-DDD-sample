# テスト自動化戦略: 蔵書検索画面

**Feature**: 004-book-search-ui
**Created**: 2025-12-24

---

## 推奨テストツール構成

### テストピラミッド

```
        /\
       /  \      E2E (Playwright)
      /----\     少数・高コスト・高信頼性
     /      \
    /--------\   統合テスト (Vitest + RTL + MSW)
   /          \  中程度
  /------------\
 /              \ ユニットテスト (Vitest + RTL)
/________________\ 多数・低コスト・高速
```

### ツール一覧

| ツール | 用途 | バージョン |
|--------|------|-----------|
| **Vitest** | テストランナー | ^2.1.x |
| **@testing-library/react** | Reactコンポーネントテスト | ^16.x |
| **@testing-library/jest-dom** | DOMマッチャー拡張 | ^6.x |
| **@testing-library/user-event** | ユーザー操作シミュレーション | ^14.x |
| **MSW (Mock Service Worker)** | APIモック | ^2.x |
| **Playwright** | E2Eテスト | ^1.x |

---

## パッケージインストール

```bash
# ユニット・統合テスト用
npm install -D vitest @vitest/ui @vitest/coverage-v8
npm install -D @testing-library/react @testing-library/jest-dom @testing-library/user-event
npm install -D jsdom
npm install -D msw

# E2Eテスト用
npm install -D @playwright/test
npx playwright install
```

---

## テストケース割り当て

### 凡例

| アイコン | テストレベル | ツール |
|---------|-------------|--------|
| 🔵 | ユニットテスト | Vitest + RTL |
| 🟢 | 統合テスト | Vitest + RTL + MSW |
| 🟠 | E2Eテスト | Playwright |

---

## 正常系テストケース (normal.md)

| ID | テストケース | レベル | ツール | 理由 |
|----|-------------|--------|--------|------|
| TC-N001 | タイトルでの部分一致検索 | 🟠 E2E | Playwright | 実際のAPI連携を含む完全なフロー |
| TC-N002 | 著者名での部分一致検索 | 🟠 E2E | Playwright | 実際のAPI連携を含む完全なフロー |
| TC-N003 | タイトルと著者の複合検索 | 🟠 E2E | Playwright | 実際のAPI連携を含む完全なフロー |
| TC-N004 | ローディング表示 | 🟢 統合 | Vitest + MSW | APIモックで遅延をシミュレート |
| TC-N005 | 検索結果件数表示 | 🟢 統合 | Vitest + MSW | レスポンスデータの表示確認 |
| TC-N006 | 貸出可能バッジ表示 | 🔵 ユニット | Vitest + RTL | BookStatusBadgeコンポーネント単体 |
| TC-N007 | 貸出中バッジ表示 | 🔵 ユニット | Vitest + RTL | BookStatusBadgeコンポーネント単体 |
| TC-N008 | 予約ありバッジ表示 | 🔵 ユニット | Vitest + RTL | BookStatusBadgeコンポーネント単体 |
| TC-N009 | ISBN-13検索 | 🟠 E2E | Playwright | 実際のAPI連携 |
| TC-N010 | ISBN-10検索 | 🟠 E2E | Playwright | 実際のAPI連携 |
| TC-N011 | ページネーション表示 | 🔵 ユニット | Vitest + RTL | Paginationコンポーネント単体 |
| TC-N012 | ページ番号クリック | 🟢 統合 | Vitest + MSW | ページ遷移とAPI呼び出し |
| TC-N013 | 「次へ」ボタン | 🟢 統合 | Vitest + MSW | ページ遷移とAPI呼び出し |
| TC-N014 | 「前へ」ボタン | 🟢 統合 | Vitest + MSW | ページ遷移とAPI呼び出し |
| TC-N015 | 0件時メッセージ | 🟢 統合 | Vitest + MSW | 空レスポンスの表示確認 |
| TC-N016 | 0件時ヒント表示 | 🔵 ユニット | Vitest + RTL | BookSearchResultsコンポーネント |
| TC-N017 | 条件なし全件検索 | 🟠 E2E | Playwright | 実際のAPI連携 |

**サマリー**: ユニット 4件 / 統合 7件 / E2E 6件

---

## 異常系テストケース (error.md)

| ID | テストケース | レベル | ツール | 理由 |
|----|-------------|--------|--------|------|
| TC-E001 | API接続エラー（サーバー停止） | 🟢 統合 | Vitest + MSW | ネットワークエラーをモック |
| TC-E002 | ネットワーク切断 | 🟢 統合 | Vitest + MSW | オフライン状態をモック |
| TC-E003 | API 500エラー | 🟢 統合 | Vitest + MSW | サーバーエラーをモック |
| TC-E004 | API 503エラー | 🟢 統合 | Vitest + MSW | サービス利用不可をモック |
| TC-E005 | API 422エラー | 🟢 統合 | Vitest + MSW | バリデーションエラーをモック |
| TC-E006 | タイムアウト | 🟢 統合 | Vitest + MSW | 遅延レスポンスをモック |
| TC-E007 | エラー後の再試行成功 | 🟢 統合 | Vitest + MSW | エラー→成功の連続モック |
| TC-E008 | エラー後の再試行失敗 | 🟢 統合 | Vitest + MSW | 連続エラーをモック |
| TC-E009 | CORSエラー | 🟠 E2E | Playwright | 実環境でのCORS検証 |
| TC-E010 | 不正なJSONレスポンス | 🟢 統合 | Vitest + MSW | 不正レスポンスをモック |
| TC-E011 | 必須フィールド欠落 | 🟢 統合 | Vitest + MSW | 不完全レスポンスをモック |
| TC-E012 | API 401エラー | 🟢 統合 | Vitest + MSW | 認証エラーをモック |
| TC-E013 | API 403エラー | 🟢 統合 | Vitest + MSW | 権限エラーをモック |

**サマリー**: ユニット 0件 / 統合 12件 / E2E 1件

---

## エッジケーステストケース (edge-cases.md)

| ID | テストケース | レベル | ツール | 理由 |
|----|-------------|--------|--------|------|
| TC-EC001 | 最大文字数入力（255文字） | 🔵 ユニット | Vitest + RTL | BookSearchFormのバリデーション |
| TC-EC002 | 最大文字数超過（256文字以上） | 🔵 ユニット | Vitest + RTL | BookSearchFormのバリデーション |
| TC-EC003 | 空白のみの入力 | 🔵 ユニット | Vitest + RTL | 入力値のトリム処理 |
| TC-EC004 | 特殊文字（XSS対策） | 🔵 ユニット | Vitest + RTL | エスケープ処理確認 |
| TC-EC005 | 日本語・英語・記号混在 | 🔵 ユニット | Vitest + RTL | マルチバイト文字処理 |
| TC-EC006 | 絵文字入力 | 🔵 ユニット | Vitest + RTL | Unicode処理 |
| TC-EC007 | ISBN-13（ハイフンあり） | 🔵 ユニット | Vitest + RTL | ISBN正規化処理 |
| TC-EC008 | ISBN-10形式 | 🔵 ユニット | Vitest + RTL | ISBN形式対応 |
| TC-EC009 | 無効なISBN形式 | 🔵 ユニット | Vitest + RTL | バリデーションエラー |
| TC-EC010 | 最初のページで「前へ」 | 🔵 ユニット | Vitest + RTL | Pagination disabled状態 |
| TC-EC011 | 最後のページで「次へ」 | 🔵 ユニット | Vitest + RTL | Pagination disabled状態 |
| TC-EC012 | 1ページのみの結果 | 🔵 ユニット | Vitest + RTL | Pagination非表示 |
| TC-EC013 | 存在しないページ番号 | 🟢 統合 | Vitest + MSW | API側のフォールバック |
| TC-EC014 | 負のページ番号 | 🔵 ユニット | Vitest + RTL | 入力値バリデーション |
| TC-EC015 | 大量データ（1000件以上） | 🟠 E2E | Playwright | 実データでのパフォーマンス |
| TC-EC016 | 検索結果0件 | 🟢 統合 | Vitest + MSW | 空配列レスポンス |
| TC-EC017 | 検索中に再度クリック | 🟢 統合 | Vitest + MSW | ボタン無効化確認 |
| TC-EC018 | 検索中に条件変更 | 🟢 統合 | Vitest + MSW | クエリキャンセル |
| TC-EC019 | 高速連続ページ遷移 | 🟢 統合 | Vitest + MSW | レースコンディション |
| TC-EC020 | ブラウザの戻る/進む | 🟠 E2E | Playwright | ブラウザ履歴操作 |
| TC-EC021 | ページリロード | 🟠 E2E | Playwright | 状態復元 |
| TC-EC022 | 長いタイトルの表示 | 🔵 ユニット | Vitest + RTL | CSS処理確認 |
| TC-EC023 | 長い著者名の表示 | 🔵 ユニット | Vitest + RTL | CSS処理確認 |

**サマリー**: ユニット 16件 / 統合 4件 / E2E 3件

---

## 全体サマリー

| テストレベル | 件数 | 割合 | 実行時間目安 |
|-------------|------|------|-------------|
| 🔵 ユニットテスト | 20件 | 38% | ~5秒 |
| 🟢 統合テスト | 23件 | 43% | ~30秒 |
| 🟠 E2Eテスト | 10件 | 19% | ~2分 |
| **合計** | **53件** | 100% | ~3分 |

---

## ディレクトリ構成（推奨）

```
frontend/
├── src/
│   ├── features/books/
│   │   ├── components/
│   │   │   ├── BookSearchForm.tsx
│   │   │   ├── BookSearchForm.test.tsx      # ユニットテスト
│   │   │   ├── BookSearchResults.tsx
│   │   │   ├── BookSearchResults.test.tsx   # ユニットテスト
│   │   │   ├── BookStatusBadge.tsx
│   │   │   ├── BookStatusBadge.test.tsx     # ユニットテスト
│   │   │   ├── Pagination.tsx
│   │   │   └── Pagination.test.tsx          # ユニットテスト
│   │   ├── hooks/
│   │   │   ├── useBookSearch.ts
│   │   │   └── useBookSearch.test.ts        # フックテスト
│   │   └── pages/
│   │       ├── BookSearchPage.tsx
│   │       └── BookSearchPage.test.tsx      # 統合テスト
│   └── mocks/
│       ├── handlers.ts                       # MSWハンドラー
│       ├── browser.ts                        # ブラウザ用MSW
│       └── server.ts                         # テスト用MSW
├── tests/
│   └── e2e/
│       ├── book-search.spec.ts              # E2Eテスト
│       └── fixtures/                         # テストデータ
├── vitest.config.ts
├── playwright.config.ts
└── package.json
```

---

## 設定ファイル例

### vitest.config.ts

```typescript
import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: ['src/mocks/**', 'src/test/**'],
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },
})
```

### playwright.config.ts

```typescript
import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:5173',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: !process.env.CI,
  },
})
```

### src/test/setup.ts

```typescript
import '@testing-library/jest-dom'
import { cleanup } from '@testing-library/react'
import { afterEach, beforeAll, afterAll } from 'vitest'
import { server } from '@/mocks/server'

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }))
afterEach(() => {
  cleanup()
  server.resetHandlers()
})
afterAll(() => server.close())
```

---

## npm scripts（推奨）

```json
{
  "scripts": {
    "test": "vitest",
    "test:ui": "vitest --ui",
    "test:coverage": "vitest --coverage",
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui",
    "test:all": "npm run test && npm run test:e2e"
  }
}
```

---

## CI/CD統合（GitHub Actions例）

```yaml
name: Test

on: [push, pull_request]

jobs:
  unit-integration:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: cd frontend && npm ci
      - run: cd frontend && npm run test:coverage

  e2e:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: cd frontend && npm ci
      - run: npx playwright install --with-deps
      - run: cd frontend && npm run test:e2e
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: playwright-report
          path: frontend/playwright-report/
```

---

## 実装優先順位

### Phase 1: 基盤構築
1. Vitest + RTL + MSW のセットアップ
2. テストヘルパー・モック作成

### Phase 2: ユニットテスト（20件）
1. BookStatusBadge（TC-N006〜N008）
2. Pagination（TC-N011, TC-EC010〜EC012）
3. BookSearchForm（TC-EC001〜EC009）
4. BookSearchResults（TC-N016, TC-EC022〜EC023）

### Phase 3: 統合テスト（23件）
1. useBookSearchフック
2. BookSearchPage（API連携）
3. エラーハンドリング（TC-E001〜E013）

### Phase 4: E2Eテスト（10件）
1. Playwrightセットアップ
2. 基本検索フロー（TC-N001〜N003）
3. ブラウザ操作（TC-EC020〜EC021）
