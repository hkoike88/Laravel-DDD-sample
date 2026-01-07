# EPIC-005: CI/CD 環境構築

最終更新: 2025-12-23

---

## 概要

GitHub Actions を使用した CI パイプラインと、pre-commit フックによるローカル品質チェックを構築する。コードの品質を継続的に維持し、問題を早期に発見できる仕組みを整備する。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-000: 開発環境構築](../../../../01_vision/initiatives/INI-000/charter.md) |
| Use Case | [UC-000-005: CI/CD 環境構築](../../../../01_vision/initiatives/INI-000/usecases/UC-000-005_CICD環境構築.md) |
| 優先度 | Should |
| ステータス | Draft |

---

## ビジネス価値

- コード品質を自動的にチェックし、バグの早期発見を実現する
- プルリクエストのレビュー効率を向上させる
- チーム全体で一貫したコード品質を維持する
- コミット前に問題を発見し、CI の失敗を防ぐ

---

## 受け入れ条件

1. プッシュ・PR 時に GitHub Actions CI が自動実行されること
2. バックエンドの静的解析（PHPStan, Pint）とテスト（Pest）が CI で実行されること
3. フロントエンドの静的解析（ESLint, Prettier, TypeScript）とテスト（Vitest）が CI で実行されること
4. pre-commit フックでコミット前にコード品質がチェックされること
5. CI が失敗した場合、PR がマージできないこと（ブランチ保護設定）

---

## 技術スタック

| 項目 | 技術 | バージョン |
|------|------|-----------|
| CI プラットフォーム | GitHub Actions | - |
| PHP | PHP | 8.3 |
| Node.js | Node.js | 20 |
| テスト DB | MySQL | 8.0 |
| pre-commit | Git Hooks | - |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_GitHub-Actions-ワークフロー作成.md) | GitHub Actions ワークフローの作成 | 2 | Must | Draft |
| [ST-002](./stories/ST-002_バックエンドCIジョブ設定.md) | バックエンド CI ジョブの設定 | 3 | Must | Draft |
| [ST-003](./stories/ST-003_フロントエンドCIジョブ設定.md) | フロントエンド CI ジョブの設定 | 2 | Must | Draft |
| [ST-004](./stories/ST-004_Pre-commitフック設定.md) | Pre-commit フックの設定 | 2 | Should | Draft |
| [ST-005](./stories/ST-005_CI動作確認.md) | CI/CD 動作確認 | 1 | Must | Draft |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| CI ワークフロー | .github/workflows/ci.yml | GitHub Actions 定義 |
| テスト用環境設定 | backend/.env.testing | CI 用環境変数 |
| pre-commit フック | .git/hooks/pre-commit | ローカル品質チェック |
| pre-commit 設定スクリプト | scripts/setup-hooks.sh | フック設定用スクリプト |

---

## CI パイプライン構成

```
┌─────────────────────────────────────────────────────────────┐
│                    GitHub Actions CI                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Trigger: push / pull_request                               │
│  Branches: master, main, develop                            │
│                                                             │
│  ┌─────────────────────┐    ┌─────────────────────┐        │
│  │    Backend Job      │    │   Frontend Job      │        │
│  │                     │    │                     │        │
│  │  1. Checkout        │    │  1. Checkout        │        │
│  │  2. Setup PHP 8.3   │    │  2. Setup Node 20   │        │
│  │  3. Composer cache  │    │  3. npm cache       │        │
│  │  4. Install deps    │    │  4. npm ci          │        │
│  │  5. PHPStan         │    │  5. ESLint          │        │
│  │  6. Pint            │    │  6. Prettier        │        │
│  │  7. Pest tests      │    │  7. TypeScript      │        │
│  │                     │    │  8. Build           │        │
│  │  [MySQL 8.0 Service]│    │  9. Vitest          │        │
│  └─────────────────────┘    └─────────────────────┘        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Pre-commit フック構成

```
┌─────────────────────────────────────────────────────────────┐
│                    Pre-commit Hook                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Staged Files Detection                                     │
│        │                                                    │
│        ├── *.php ──────→ PHPStan + Pint                    │
│        │                                                    │
│        └── *.ts/*.tsx ──→ ESLint + Prettier + TypeScript   │
│                                                             │
│  All Checks Pass? ──→ Commit Allowed                       │
│         │                                                   │
│         └── No ──→ Commit Rejected                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 依存関係

### 前提条件

| Epic ID | Epic 名 | 関係 |
|---------|---------|------|
| EPIC-002 | バックエンド初期設定 | PHPStan, Pint, Pest が設定済み |
| EPIC-003 | フロントエンド初期設定 | ESLint, Prettier, Vitest が設定済み |
| EPIC-004 | 開発環境動作確認 | テストが実行可能な状態 |

### 後続タスク

本 Epic が完了すると、開発環境構築イニシアチブ（INI-000）が完了となり、品質を維持しながら機能開発を開始できる状態となる。

---

## リスクと対策

| リスク | 影響 | 対策 |
|--------|------|------|
| CI 実行時間が長い | 開発効率低下 | キャッシュ活用、並列実行 |
| MySQL サービス起動失敗 | テスト実行不可 | health check オプション設定 |
| pre-commit が重い | コミット時間増加 | 変更ファイルのみチェック |
| CI と ローカル環境の差異 | 予期せぬ失敗 | 同一バージョン指定 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
