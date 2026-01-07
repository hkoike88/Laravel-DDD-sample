# EPIC-003: フロントエンド初期設定

最終更新: 2025-12-23

---

## 概要

React + TypeScript + Vite プロジェクトを作成し、Feature-based アーキテクチャに基づいたディレクトリ構成、必要なパッケージのインストール、基本設定を完了する。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-000: 開発環境構築](../../../../01_vision/initiatives/INI-000/charter.md) |
| Use Case | [UC-000-003: フロントエンド初期設定](../../../../01_vision/initiatives/INI-000/usecases/UC-000-003_フロントエンド初期設定.md) |
| 優先度 | Must |
| ステータス | Draft |

---

## ビジネス価値

開発チームが React + TypeScript で開発を開始できる基盤を整備する。
Feature-based アーキテクチャを導入し、スケーラブルで保守性の高いフロントエンド構成を実現する。

---

## 受け入れ条件

1. 開発サーバーが正常に起動すること
2. http://localhost:5173 でアプリケーションが表示されること
3. TypeScript の型チェックが通ること
4. ESLint / Prettier が動作すること
5. Tailwind CSS が正しく適用されること
6. Feature-based ディレクトリ構成が作成されていること

---

## 技術スタック

| 項目 | 技術 | バージョン |
|------|------|-----------|
| 言語 | TypeScript | 5.x |
| UIライブラリ | React | 18.x |
| ビルドツール | Vite | 6.x |
| ルーティング | React Router | 7.x |
| サーバー状態管理 | TanStack Query | 5.x |
| クライアント状態管理 | Zustand | 5.x |
| HTTP クライアント | Axios | 1.x |
| フォーム管理 | React Hook Form | 7.x |
| バリデーション | Zod | 3.x |
| CSSフレームワーク | Tailwind CSS | 3.x |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_Viteプロジェクト作成.md) | Vite + React + TypeScript プロジェクトの作成 | 2 | Must | Draft |
| [ST-002](./stories/ST-002_ディレクトリ構成作成.md) | Feature-based ディレクトリ構成の作成 | 2 | Must | Draft |
| [ST-003](./stories/ST-003_パッケージインストール.md) | 必要なパッケージのインストール | 2 | Must | Draft |
| [ST-004](./stories/ST-004_ESLint-Prettier設定.md) | ESLint / Prettier の設定 | 2 | Must | Draft |
| [ST-005](./stories/ST-005_TailwindCSS設定.md) | Tailwind CSS の設定 | 2 | Must | Draft |
| [ST-006](./stories/ST-006_開発サーバー起動確認.md) | 開発サーバーの起動確認 | 1 | Must | Draft |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| React プロジェクト一式 | frontend/ | アプリケーション本体 |
| Feature-based ディレクトリ構成 | frontend/src/ | 機能ベース設計用構造 |
| TypeScript 設定 | frontend/tsconfig.json | TypeScript コンパイル設定 |
| Vite 設定 | frontend/vite.config.ts | ビルドツール設定 |
| Tailwind 設定 | frontend/tailwind.config.js | CSS フレームワーク設定 |
| ESLint 設定 | frontend/eslint.config.js | コード品質チェック設定 |

---

## Feature-based ディレクトリ構成

```
frontend/
├── src/
│   ├── app/                    # アプリケーション設定
│   │   ├── App.tsx
│   │   ├── router.tsx
│   │   └── providers/
│   ├── pages/                  # ページコンポーネント
│   ├── features/               # 機能モジュール
│   ├── components/             # 共通 UI コンポーネント
│   │   ├── ui/
│   │   └── layout/
│   ├── hooks/                  # 共通 Hooks
│   ├── lib/                    # ユーティリティ
│   └── types/                  # グローバル型定義
├── public/
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
├── tailwind.config.js
├── postcss.config.js
└── eslint.config.js
```

---

## 依存関係

### 前提条件

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-001 | Docker 環境構築 | 本 Epic の前提 |

### 後続タスク

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-004 | 開発環境動作確認 | 本 Epic の後に実施 |

---

## リスクと対策

| リスク | 影響 | 対策 |
|--------|------|------|
| パッケージバージョン競合 | ビルド失敗 | package-lock.json をコミット |
| Vite 設定ミス | 開発サーバー起動失敗 | 公式ドキュメントに従って設定 |
| TypeScript 設定不備 | 型チェックエラー多発 | strict モードを段階的に導入 |
| Tailwind 設定漏れ | スタイル未適用 | content パスを正しく設定 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
