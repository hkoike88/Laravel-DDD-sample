# Quickstart: ダッシュボード画面

**Feature**: 004-dashboard-ui
**Date**: 2025-12-25

## Prerequisites

- Node.js 20.x
- Docker Compose（バックエンド実行用）
- 003-login-ui が実装済み

## Setup

```bash
# 1. フィーチャーブランチに切り替え
git checkout 004-dashboard-ui

# 2. バックエンド起動（認証 API 必要）
docker compose up -d

# 3. フロントエンド依存関係インストール
cd frontend
npm install

# 4. フロントエンド開発サーバー起動
npm run dev
```

## Access

- フロントエンド: http://localhost:5173
- ログイン画面: http://localhost:5173/login
- ダッシュボード: http://localhost:5173/dashboard（要ログイン）

## Test Credentials

```
Email: staff@example.com
Password: password123
```

## Manual Testing Scenarios

### Scenario 1: ダッシュボード表示

1. http://localhost:5173/login にアクセス
2. テスト認証情報でログイン
3. ダッシュボード画面が表示される
4. 確認項目:
   - [ ] ウェルカムメッセージに職員名が表示される
   - [ ] 5つの業務メニューカードが表示される
   - [ ] ヘッダーにナビゲーションが表示される
   - [ ] ヘッダーに職員名が表示される

### Scenario 2: メニュー遷移

1. ダッシュボードで「蔵書管理」カードをクリック
2. /books ページに遷移する
3. 確認項目:
   - [ ] 画面が遷移する
   - [ ] ヘッダー/フッターが維持される

### Scenario 3: ログアウト

1. ヘッダーの職員名をクリック
2. 「ログアウト」をクリック
3. ログイン画面に遷移する
4. 確認項目:
   - [ ] セッションが終了する
   - [ ] /dashboard に直接アクセスしても /login にリダイレクトされる

### Scenario 4: 未実装メニュー

1. 「統計」メニューをクリック（enabled: false の場合）
2. 確認項目:
   - [ ] 「準備中」メッセージが表示される
   - [ ] または カードが無効化されている

### Scenario 5: レスポンシブ表示

1. ブラウザ幅を縮小（768px 以下）
2. 確認項目:
   - [ ] メニューカードが1列または2列に変更
   - [ ] ヘッダーナビがハンバーガーメニューに変更

## Run Tests

```bash
cd frontend

# ユニットテスト
npm test

# E2E テスト（Playwright）
npm run test:e2e

# 特定のテストファイル
npm test -- src/features/dashboard/
npm run test:e2e -- tests/e2e/dashboard.spec.ts
```

## Key Files

| ファイル | 説明 |
|---------|------|
| `src/features/dashboard/pages/DashboardPage.tsx` | ダッシュボードページ |
| `src/features/dashboard/components/MenuCard.tsx` | メニューカード |
| `src/features/dashboard/components/MenuGrid.tsx` | メニューグリッド |
| `src/components/layout/Header.tsx` | 共通ヘッダー |
| `src/components/layout/Footer.tsx` | 共通フッター |
| `src/components/layout/MainLayout.tsx` | 共通レイアウト |
| `src/features/auth/hooks/useLogout.ts` | ログアウトフック |

## Troubleshooting

### ダッシュボードにアクセスできない

1. ログイン済みか確認（DevTools → Application → Cookies で session 確認）
2. バックエンドが起動しているか確認（`docker compose ps`）
3. CORS 設定確認（`backend/config/cors.php`）

### ログアウトが失敗する

1. ネットワークエラー: バックエンド接続確認
2. 401 エラー: セッション期限切れ → 再ログイン

### スタイルが適用されない

1. Tailwind CSS がビルドに含まれているか確認
2. `npm run dev` を再起動
