# Data Model: フロントエンド初期設定

**Date**: 2025-12-23
**Feature**: 004-frontend-setup
**Status**: Initial Setup (No domain entities yet)

## Overview

このフィーチャーはフロントエンド初期設定であり、具体的なドメインエンティティの実装は含まれません。ここでは Feature-based アーキテクチャのレイヤー構成と、将来のエンティティ配置ガイドラインを文書化します。

## Feature-based Layer Architecture

### Layer Responsibilities

| Layer | Directory | Responsibility | Example Components |
|-------|-----------|----------------|-------------------|
| **Application** | app/ | アプリケーション設定、ルーティング、プロバイダー | App.tsx, router.tsx, providers/ |
| **Pages** | pages/ | ページレベルのコンポーネント、ルートコンポーネント | HomePage, BookListPage |
| **Features** | features/ | 機能モジュール（ドメインロジック + UI） | BookManagement/, LoanManagement/ |
| **Components** | components/ | 共通 UI コンポーネント | ui/Button, layout/Header |
| **Hooks** | hooks/ | 共通カスタムフック | useAuth, useDebounce |
| **Lib** | lib/ | ユーティリティ、API クライアント設定 | axios.ts, queryClient.ts |
| **Types** | types/ | グローバル型定義 | api.d.ts, common.d.ts |

### Feature Module Structure

各機能モジュール（features/）は以下の構造を持つ：

```text
features/{FeatureName}/
├── api/           # API 呼び出し（TanStack Query hooks）
├── components/    # 機能固有の UI コンポーネント
├── hooks/         # 機能固有のカスタムフック
├── types/         # 機能固有の型定義
└── index.ts       # 公開エクスポート
```

## Configuration Files

### Package Dependencies

| Category | Package | Version | Purpose |
|----------|---------|---------|---------|
| Core | react | 18.x | UI ライブラリ |
| Core | react-dom | 18.x | DOM レンダリング |
| Build | vite | 6.x | ビルドツール |
| Routing | react-router-dom | 7.x | クライアントサイドルーティング |
| State | @tanstack/react-query | 5.x | サーバー状態管理 |
| State | zustand | 5.x | クライアント状態管理 |
| HTTP | axios | 1.x | HTTP クライアント |
| Forms | react-hook-form | 7.x | フォーム管理 |
| Validation | zod | 3.x | スキーマバリデーション |
| Styling | tailwindcss | 3.x | CSS フレームワーク |
| Dev | typescript | 5.x | 型チェック |
| Dev | eslint | 9.x | コード品質チェック |
| Dev | prettier | 3.x | コードフォーマット |

### Configuration File Mapping

| File | Purpose | Key Settings |
|------|---------|--------------|
| package.json | 依存関係管理 | dependencies, scripts |
| tsconfig.json | TypeScript 設定 | strict: true, paths: {"@/*": ["./src/*"]} |
| vite.config.ts | Vite 設定 | server.host, resolve.alias |
| tailwind.config.js | Tailwind 設定 | content paths |
| postcss.config.js | PostCSS 設定 | tailwindcss plugin |
| eslint.config.js | ESLint 設定 | typescript, react rules |
| .prettierrc | Prettier 設定 | semi, singleQuote, etc. |

## Planned Entities (Future Features)

以下のエンティティが将来的に実装される予定です（本フィーチャーでは空のディレクトリのみ作成）:

### BookManagement（書籍管理）

| Entity | TypeScript Interface | API Endpoint |
|--------|---------------------|--------------|
| Book | `{ id, isbn, title, author, publishedAt }` | GET /api/books |

### LoanManagement（貸出管理）

| Entity | TypeScript Interface | API Endpoint |
|--------|---------------------|--------------|
| Loan | `{ id, bookId, userId, borrowedAt, dueAt, returnedAt }` | GET /api/loans |

### UserManagement（ユーザー管理）

| Entity | TypeScript Interface | API Endpoint |
|--------|---------------------|--------------|
| User | `{ id, name, email }` | GET /api/users |

## Notes

- 本フィーチャーはディレクトリ構成と開発ツールの設定が主目的
- 具体的なエンティティ実装は後続フィーチャーで行う
- バックエンド API との連携は EPIC-002 完了後に実装
