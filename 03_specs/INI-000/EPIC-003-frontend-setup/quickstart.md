# Quickstart: フロントエンド初期設定

**Date**: 2025-12-23
**Feature**: 004-frontend-setup

## 前提条件

- EPIC-001（Docker 環境構築）が完了していること
- Docker コンテナが起動していること

```bash
# Docker 環境の起動確認
docker compose ps
```

すべてのサービスが「Running」状態であることを確認してください。

---

## セットアップ手順

### 1. Vite プロジェクトの作成（初回のみ）

```bash
# フロントエンドコンテナに入る
docker compose exec frontend bash

# Vite プロジェクトの作成（React + TypeScript テンプレート）
npm create vite@latest . -- --template react-ts

# 依存関係のインストール
npm install
```

### 2. 追加パッケージのインストール

```bash
# コンテナ内で実行

# ルーティング
npm install react-router-dom

# 状態管理
npm install @tanstack/react-query zustand

# HTTP クライアント
npm install axios

# フォーム管理
npm install react-hook-form zod @hookform/resolvers

# Tailwind CSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# ESLint / Prettier
npm install -D prettier eslint-config-prettier eslint-plugin-prettier
```

### 3. Feature-based ディレクトリ構成の作成

```bash
# コンテナ内で実行
mkdir -p src/app/providers
mkdir -p src/pages
mkdir -p src/features
mkdir -p src/components/ui
mkdir -p src/components/layout
mkdir -p src/hooks
mkdir -p src/lib
mkdir -p src/types
```

### 4. Vite 設定の更新

`vite.config.ts` を以下のように更新：

```typescript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
})
```

### 5. TypeScript パスエイリアス設定

`tsconfig.json` に以下を追加：

```json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      "@/*": ["./src/*"]
    }
  }
}
```

### 6. Tailwind CSS 設定

`tailwind.config.js` を以下のように更新：

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

`src/index.css` の先頭に追加：

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

---

## 動作確認

### 開発サーバー起動

```bash
docker compose exec frontend npm run dev
# 期待: VITE v6.x.x ready in xxx ms
```

ブラウザで http://localhost:5173 にアクセス

### TypeScript 型チェック

```bash
docker compose exec frontend npx tsc --noEmit
# 期待: エラー 0 件
```

### ESLint チェック

```bash
docker compose exec frontend npm run lint
# 期待: エラー 0 件
```

### プロダクションビルド

```bash
docker compose exec frontend npm run build
# 期待: dist/ ディレクトリが生成される
```

---

## トラブルシューティング

### 開発サーバーにアクセスできない

```bash
# vite.config.ts の server.host が '0.0.0.0' になっているか確認
# Docker ポートマッピング（5173:5173）が設定されているか確認
```

### npm install でエラー

```bash
# node_modules を削除して再インストール
rm -rf node_modules package-lock.json
npm install
```

### TypeScript パスエイリアスが解決されない

```bash
# tsconfig.json と vite.config.ts の両方で設定されているか確認
# Vite を再起動
```

---

## 次のステップ

1. ページコンポーネントの実装（EPIC 後続）
2. バックエンド API との連携（EPIC-002 完了後）
3. 認証機能の実装
