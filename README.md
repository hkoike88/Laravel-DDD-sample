# laravel-ddd-library

**Laravel + React で学ぶ DDD とアジャイル開発のサンプルプロジェクト**

図書館業務デジタル化を題材にした学習教材です。

## プロジェクト概要

本プロジェクトは、**アジャイル開発** と **ドメイン駆動設計（DDD）** を体系的に学ぶための疑似プロジェクト教材です。

架空の図書館システムのデジタル化を題材に、以下を実践的に学習できます：

- **アジャイル開発**: Epic / Story / Task の分解、スプリント計画、バックログ管理
- **DDD（ドメイン駆動設計）**: Laravel でのドメイン別グループ化アーキテクチャ
- **モダンフロントエンド**: React + TypeScript による SPA 開発

### なぜ「図書館」を題材にしているか

- IT 初学者にも理解しやすいドメイン
- Excel 運用 → システム化の変換が明確
- 業務がシンプル（貸出・返却・検索・予約）で説明しやすい
- 拡張（通知、分析、アプリ化）も容易でアジャイル演習に最適

---

## 技術スタック

### バックエンド

| 項目 | 技術 |
|------|------|
| 言語 | PHP 8.3 |
| フレームワーク | Laravel 11.x |
| アーキテクチャ | DDD（ドメイン別グループ化） |
| ORM | Eloquent（Domain Model と分離） |
| 認証 | Laravel Sanctum |
| テスト | Pest |
| ID 生成 | ULID（symfony/uid） |

### フロントエンド

| 項目 | 技術 |
|------|------|
| 言語 | TypeScript 5.x |
| UI ライブラリ | React 18.x |
| ビルドツール | Vite 6.x |
| 状態管理 | TanStack Query 5.x + Zustand 5.x |
| スタイリング | Tailwind CSS |
| フォーム | React Hook Form + Zod |

### インフラ

| 項目 | 技術 |
|------|------|
| コンテナ | Docker / Docker Compose |
| Web サーバー | Nginx |
| データベース | MySQL 8.0 |

---

## アーキテクチャ

### DDD レイヤー構成

本プロジェクトは **ドメイン駆動設計（DDD）** に基づくレイヤードアーキテクチャを採用しています。

```
┌─────────────────────────────────────────────────────┐
│                  Presentation                        │
│              (Controller / CLI / API)                │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                    Application                       │
│                  (UseCase / DTO)                     │
└─────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────┐
│                      Domain                          │
│          (Entity / ValueObject / Service)            │
└─────────────────────────────────────────────────────┘
                          ↑
┌─────────────────────────────────────────────────────┐
│                   Infrastructure                     │
│              (Eloquent / API / Cache)                │
└─────────────────────────────────────────────────────┘
```

### 依存関係ルール

```
[Presentation] → [Application] → [Domain] ← [Infrastructure]
```

- **Domain 層は Laravel / Eloquent に依存しない**（純粋な PHP）
- ビジネスロジックは Domain 層に集約
- Eloquent Model（`*Record.php`）と Domain Entity を分離

詳細は [バックエンドアーキテクチャ設計](./00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign.md) を参照してください。

---

## プロジェクト構成

```
laravel-ddd-library/
├── 00_docs/                # プロジェクトドキュメント
│   ├── 10_business/        # ビジネスドキュメント（業務フロー、課題分析）
│   └── 20_tech/            # 技術ドキュメント
│       ├── 10_team/        # チーム標準（DoR / DoD）
│       ├── 20_architecture/# アーキテクチャ設計（ADR、DDD設計）
│       └── 99_standard/    # 設計標準・規約
├── 01_vision/              # プロダクトビジョン・戦略
│   ├── product-vision.md   # プロダクトビジョン
│   ├── roadmap.md          # ロードマップ
│   └── initiatives/        # イニシアチブ定義
├── 02_backlog/             # プロダクトバックログ
│   └── 00_epics/           # Epic / Story 管理
│       ├── INI-000/        # 環境構築イニシアチブ
│       ├── INI-001/        # 職員管理イニシアチブ
│       └── INI-002/        # 図書館業務イニシアチブ
├── 03_specs/               # 機能仕様・設計書
│   ├── INI-000/            # 環境構築の仕様
│   ├── INI-001/            # 職員管理の仕様
│   └── INI-002/            # 図書館業務の仕様
├── 99_memo/                # 学習用メタ情報・メモ
├── 99_records/             # プロジェクト記録
│   └── meetings/           # ミーティング議事録
├── backend/                # バックエンドソースコード（PHP/Laravel）
├── frontend/               # フロントエンドソースコード（TypeScript/React）
└── infrastructure/         # 環境・インフラ設定（Docker、Nginx等）
```

### アジャイル成果物

| ディレクトリ | 内容 | 詳細 |
|-------------|------|------|
| [01_vision/](./01_vision/) | プロダクトビジョン・戦略 | ビジョン、ロードマップ、イニシアチブ定義 |
| [02_backlog/](./02_backlog/) | プロダクトバックログ | Epic / Story / Task の管理（MoSCoW 優先度付け） |
| [03_specs/](./03_specs/) | 機能仕様・設計書 | 各機能の spec.md、plan.md、tasks.md、contracts/ |
| [99_records/](./99_records/) | プロジェクト記録 | ミーティング議事録、ふりかえり記録 |

### 主要ドキュメント

| カテゴリ | ドキュメント | 内容 |
|----------|-------------|------|
| アーキテクチャ | [01_SystemOverview.md](./00_docs/20_tech/20_architecture/01_SystemOverview.md) | システム全体概要 |
| バックエンド | [01_ArchitectureDesign.md](./00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign.md) | DDD アーキテクチャ設計 |
| フロントエンド | [01_ArchitectureDesign.md](./00_docs/20_tech/20_architecture/frontend/01_ArchitectureDesign.md) | Feature-based 設計 |
| ADR | [adr/README.md](./00_docs/20_tech/20_architecture/adr/README.md) | 技術選定の記録 |
| チーム標準 | [dor.md](./00_docs/20_tech/10_team/dor.md) / [dod.md](./00_docs/20_tech/10_team/dod.md) | 着手・完了の定義 |

---

## 必要な環境

- Docker Engine 24.0+
- Docker Compose v2.20+
- Git
- Make（コマンド簡略化用）

---

## クイックスタート

### 1. 環境変数の設定

```bash
cp .env.example .env
```

### 2. Git フックの設定

```bash
git config core.hooksPath .githooks
```

このコマンドで以下の Git フックが有効化されます：
- **pre-commit**: main/master への直接コミット禁止、静的解析、セキュリティスキャン
- **pre-push**: main/master への直接プッシュ禁止

### 3. サービスの起動

```bash
make up
```

### 4. 起動確認

```bash
docker compose ps
```

すべてのサービスが「Running」状態であることを確認してください。

### 5. 初期データ投入（任意）

```bash
make migrate     # マイグレーション
make seed-books  # サンプル蔵書データ
```

---

## ブランチ戦略

本プロジェクトでは、**main ブランチへの直接コミット・プッシュを禁止**しています。

### ブランチ構成

```
main (本番相当)
  ↑
develop (開発統合)
  ↑
feature/xxx (機能開発)
```

### 開発フロー

1. **feature ブランチを作成**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **開発・コミット**
   ```bash
   git add .
   git commit -m "your message"
   ```

   ※ pre-commit フックで自動的に以下がチェックされます：
   - 静的解析（PHPStan, ESLint, TypeScript）
   - コードスタイル（Pint, Prettier）
   - セキュリティスキャン（Composer Audit, npm Audit）

3. **feature ブランチをプッシュ**
   ```bash
   git push origin feature/your-feature-name
   ```

4. **GitHub でプルリクエストを作成**
   - `feature/xxx` → `develop` または `main`
   - レビュー後にマージ

### Git フック

プロジェクトには以下の Git フックが設定されています：

| フック | 役割 |
|--------|------|
| **pre-commit** | ・main/master への直接コミット禁止<br>・静的解析実行（PHPStan, ESLint, TypeScript）<br>・コードフォーマットチェック（Pint, Prettier）<br>・セキュリティスキャン（Critical/High のみ） |
| **pre-push** | ・main/master への直接プッシュ禁止<br>・GitHub でのプルリクエスト作成を推奨 |

※ フックを有効化するには `git config core.hooksPath .githooks` を実行してください

### Claude によるコードレビュー

main ブランチへのプルリクエスト作成時に、**Claude が自動的にコードレビュー**を実行します。

**レビュー観点**:
- コーディング規約（PSR-12, TypeScript/React）
- セキュリティ（SQL インジェクション, XSS, CSRF）
- アーキテクチャ（DDD 原則, レイヤー分離）
- パフォーマンス（N+1 問題, メモリリーク）
- テスト、ドキュメント

**セットアップ**: [.github/CLAUDE_REVIEW_SETUP.md](./.github/CLAUDE_REVIEW_SETUP.md) を参照してください

---

## アクセス

| サービス | URL | 用途 |
|----------|-----|------|
| メインアプリ | http://localhost | フロントエンド（Nginx 経由） |
| API | http://localhost/api | バックエンド API（Nginx 経由） |
| フロントエンド（直接） | http://localhost:5173 | Vite 開発サーバー |
| phpMyAdmin | http://localhost:8080 | データベース管理 |

---

## 基本コマンド（Makefile）

本プロジェクトでは Makefile を使用してコマンドを簡略化しています。

```bash
# 使用可能なコマンド一覧を表示
make help
```

### Docker 操作

```bash
make up        # コンテナ起動
make down      # コンテナ停止
make restart   # コンテナ再起動
make logs      # ログ表示
make shell     # バックエンドコンテナに入る
```

### テスト・Lint

```bash
make test       # 全テスト実行（Pest）
make lint       # コードスタイル修正（Pint）
make lint-check # コードスタイルチェック（修正なし）
make phpstan    # PHPStan 静的解析
```

### データベース

```bash
make migrate    # マイグレーション実行
make fresh      # DB リセット + マイグレーション
make seed       # 全シーダー実行
make seed-books # サンプル蔵書データ投入（100件以上）
```

### セキュリティスキャン

```bash
make security          # 全セキュリティスキャン
make security-backend  # バックエンドのみ
make security-frontend # フロントエンドのみ
make security-audit    # 依存パッケージ脆弱性チェック
```

---

## docker compose コマンド（参考）

Makefile を使わない場合は以下のコマンドを使用できます。

<details>
<summary>docker compose コマンド一覧</summary>

```bash
# 全サービス起動
docker compose up -d

# 全サービス停止
docker compose down

# ログ確認
docker compose logs -f

# 完全リセット（データ含む）
docker compose down -v

# バックエンドコンテナに入る
docker compose exec backend sh

# テスト実行
docker compose exec backend ./vendor/bin/pest

# 静的解析
docker compose exec backend ./vendor/bin/phpstan analyse

# フロントエンドコンテナに入る
docker compose exec frontend sh

# 型チェック
docker compose exec frontend npm run typecheck

# ビルド
docker compose exec frontend npm run build
```

</details>

---

## ポート設定のカスタマイズ

`.env` ファイルでポート番号を変更できます。

```bash
# .env
FRONTEND_PORT=3000
NGINX_PORT=8080
PHPMYADMIN_PORT=18080
```

---

## 学習ガイド

### アジャイル学習

- [01_AgileLibraryProjectExplanation.md](./99_memo/01_AgileLibraryProjectExplanation.md) - 疑似プロジェクトの説明
- [03_AgileLibraryProject.md](./99_memo/03_AgileLibraryProject.md) - プロジェクト背景と Epic 分解
- [05_PrepareBeforeSprintInAgile.md](./99_memo/05_PrepareBeforeSprintInAgile.md) - スプリント前の準備

### DDD / Laravel 学習

- [00_概要.md](./00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign/00_概要.md) - DDD の基本概念
- [01_アーキテクチャ設計.md](./00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign/01_アーキテクチャ設計.md) - レイヤー構成と設計原則
- [02_実装パターン.md](./00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign/02_実装パターン.md) - コードサンプル

---

## 参考プロジェクト

本プロジェクトの DDD アーキテクチャ設計は、以下のプロジェクトを参考にしています。

- [Orphail/laravel-ddd](https://github.com/Orphail/laravel-ddd) - Laravel DDD のディレクトリ構成・設計パターン
- [take-t14/laravel-ddd-sample](https://github.com/take-t14/laravel-ddd-sample) - Laravel DDD の実装サンプル

---
