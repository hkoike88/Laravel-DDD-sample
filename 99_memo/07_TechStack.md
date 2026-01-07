# 技術スタック定義

> **⚠️ メタ情報（学習教材）**
>
> このドキュメントは **学習で使用する技術スタックの定義** です。
>
> - このファイルを変更することで、学習する技術を切り替え可能
> - プロジェクトの要件に合わせてカスタマイズしてください

---

## 1. 採用技術スタック

### バックエンド

| 項目 | 技術 | バージョン（目安） | 備考 |
|------|------|------------------|------|
| 言語 | PHP | 8.2+ | 型宣言、属性（Attributes）を活用 |
| フレームワーク | Laravel | 11.x | REST API構築に使用 |
| ORM | Eloquent | - | Laravel標準 |
| 認証 | Laravel Sanctum | - | SPA認証・APIトークン |

### フロントエンド

| 項目 | 技術 | バージョン（目安） | 備考 |
|------|------|------------------|------|
| 言語 | TypeScript | 5.x | 型安全なコード |
| UIライブラリ | React | 18.x | 関数コンポーネント + Hooks |
| ビルドツール | Vite | 5.x | 高速な開発サーバー |
| 状態管理 | （選択可） | - | React Query / Zustand / Redux Toolkit など |
| UIフレームワーク | （選択可） | - | Tailwind CSS / MUI / shadcn/ui など |

### データベース

| 項目 | 技術 | バージョン（目安） | 備考 |
|------|------|------------------|------|
| RDBMS | MySQL | 8.x | または PostgreSQL 15+ |
| マイグレーション | Laravel Migration | - | スキーマ管理 |
| シーディング | Laravel Seeder | - | テストデータ投入 |

### 開発環境・インフラ

| 項目 | 技術 | 備考 |
|------|------|------|
| コンテナ | Docker / Docker Compose | ローカル開発環境 |
| Webサーバー | Nginx | または Laravel Sail のビルトインサーバー |
| バージョン管理 | Git | GitHub / GitLab |

---

## 2. 学習目標

### Laravel で学ぶこと

- [ ] RESTful API設計と実装
- [ ] Eloquent ORMによるDB操作
- [ ] バリデーション（FormRequest）
- [ ] 認証・認可（Sanctum、Policy）
- [ ] サービスクラス / リポジトリパターン
- [ ] テスト（PHPUnit / Pest）
- [ ] Artisanコマンドの活用

### React + TypeScript で学ぶこと

- [ ] 関数コンポーネントとHooks
- [ ] TypeScriptによる型定義
- [ ] API通信（fetch / axios）
- [ ] 状態管理の基本
- [ ] フォーム処理とバリデーション
- [ ] ルーティング（React Router）
- [ ] コンポーネント設計

---

## 3. アーキテクチャ概要

```
┌─────────────────────────────────────────────────────────┐
│                      クライアント                        │
│                   React + TypeScript                    │
│                     (SPA / Vite)                        │
└─────────────────────┬───────────────────────────────────┘
                      │ HTTP (REST API)
                      ▼
┌─────────────────────────────────────────────────────────┐
│                      バックエンド                        │
│                   Laravel (PHP 8.2+)                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │ Controller  │→ │  Service    │→ │ Repository  │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                           │             │
│                                    Eloquent ORM         │
└───────────────────────────────────────────┼─────────────┘
                                            │
                                            ▼
┌─────────────────────────────────────────────────────────┐
│                      データベース                        │
│                   MySQL 8.x / PostgreSQL                │
└─────────────────────────────────────────────────────────┘
```

---

## 4. 技術スタック変更例

このファイルを編集することで、別の技術スタックでの学習も可能です。

### 例A: Spring Boot + Vue.js

| レイヤー | 変更前 | 変更後 |
|---------|--------|--------|
| バックエンド | Laravel (PHP) | Spring Boot (Java/Kotlin) |
| フロントエンド | React + TypeScript | Vue.js 3 + TypeScript |
| ORM | Eloquent | JPA / MyBatis |

### 例B: NestJS + Next.js

| レイヤー | 変更前 | 変更後 |
|---------|--------|--------|
| バックエンド | Laravel (PHP) | NestJS (TypeScript) |
| フロントエンド | React + TypeScript | Next.js (React SSR) |
| ORM | Eloquent | Prisma / TypeORM |

### 例C: Ruby on Rails + Hotwire

| レイヤー | 変更前 | 変更後 |
|---------|--------|--------|
| バックエンド | Laravel (PHP) | Ruby on Rails |
| フロントエンド | React + TypeScript | Hotwire (Turbo + Stimulus) |
| ORM | Eloquent | Active Record |

---

## 5. 関連ドキュメント

- [プロジェクト概要（03_AgileLibraryProject.md）](./03_AgileLibraryProject.md)
- [スプリント前の準備（05_PrepareBeforeSprintInAgile.md）](./05_PrepareBeforeSprintInAgile.md)
