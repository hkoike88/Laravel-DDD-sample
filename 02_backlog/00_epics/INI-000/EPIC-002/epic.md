# EPIC-002: バックエンド初期設定

最終更新: 2025-12-23

---

## 概要

Laravel 11.x プロジェクトを作成し、DDD アーキテクチャに基づいたディレクトリ構成、必要なパッケージのインストール、基本設定を完了する。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-000: 開発環境構築](../../../../01_vision/initiatives/INI-000/charter.md) |
| Use Case | [UC-000-002: バックエンド初期設定](../../../../01_vision/initiatives/INI-000/usecases/UC-000-002_バックエンド初期設定.md) |
| 優先度 | Must |
| ステータス | Draft |

---

## ビジネス価値

開発チームが Laravel + DDD アーキテクチャで開発を開始できる基盤を整備する。
静的解析やテストフレームワークを導入し、コード品質を維持する仕組みを構築する。

---

## 受け入れ条件

1. Laravel プロジェクトが正常に動作すること
2. `php artisan` コマンドが実行できること
3. データベース接続が成功すること
4. 静的解析（PHPStan）が実行できること
5. テスト（Pest）が実行できること
6. DDD ディレクトリ構成が作成されていること

---

## 技術スタック

| 項目 | 技術 | バージョン |
|------|------|-----------|
| 言語 | PHP | 8.3 |
| フレームワーク | Laravel | 11.x |
| 認証 | Laravel Sanctum | 4.x |
| 静的解析 | Larastan | 2.x |
| テスト | Pest | 3.x |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_Laravelプロジェクト作成.md) | Laravel プロジェクトの作成 | 2 | Must | Draft |
| [ST-002](./stories/ST-002_DDDディレクトリ構成作成.md) | DDD ディレクトリ構成の作成 | 2 | Must | Draft |
| [ST-003](./stories/ST-003_開発パッケージ導入.md) | 開発パッケージの導入 | 2 | Must | Draft |
| [ST-004](./stories/ST-004_静的解析設定.md) | 静的解析（PHPStan/Larastan）の設定 | 2 | Must | Draft |
| [ST-005](./stories/ST-005_テスト環境設定.md) | テスト環境（Pest）の設定 | 2 | Must | Draft |
| [ST-006](./stories/ST-006_環境設定とDB接続確認.md) | 環境設定とデータベース接続確認 | 1 | Must | Draft |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| Laravel プロジェクト一式 | backend/ | アプリケーション本体 |
| DDD ディレクトリ構成 | backend/app/src/ | ドメイン駆動設計用構造 |
| 静的解析設定 | backend/phpstan.neon | PHPStan 設定 |
| テスト設定 | backend/phpunit.xml | Pest/PHPUnit 設定 |
| Composer 設定 | backend/composer.json | 依存パッケージ定義 |

---

## DDD ディレクトリ構成

```
backend/
├── app/
│   ├── src/                         # DDD ソースコード
│   │   ├── BookManagement/          # 書籍管理コンテキスト（将来）
│   │   │   ├── Domain/
│   │   │   │   ├── Models/
│   │   │   │   ├── Repositories/
│   │   │   │   ├── Services/
│   │   │   │   └── Exceptions/
│   │   │   ├── Application/
│   │   │   │   ├── UseCases/
│   │   │   │   ├── DTO/
│   │   │   │   └── Repositories/
│   │   │   ├── Presentation/
│   │   │   │   └── HTTP/
│   │   │   └── Infrastructure/
│   │   │       └── EloquentModels/
│   │   ├── LoanManagement/          # 貸出管理コンテキスト（将来）
│   │   ├── UserManagement/          # ユーザー管理コンテキスト（将来）
│   │   └── Common/                  # 共有リソース
│   │       ├── Domain/
│   │       └── Infrastructure/
│   ├── Http/                        # Laravel 標準（将来的に移行）
│   ├── Models/                      # Laravel 標準（将来的に移行）
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── public/
├── resources/
├── routes/
│   └── api.php
├── storage/
├── tests/
│   ├── Unit/
│   └── Feature/
├── composer.json
├── phpstan.neon
└── phpunit.xml
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
| Laravel バージョン互換性 | パッケージ動作不良 | 公式ドキュメントでバージョン確認 |
| Composer 依存解決失敗 | インストール失敗 | composer.lock をコミット |
| PHPStan レベル設定 | 厳しすぎるとエラー多発 | レベル 5 から開始し段階的に上げる |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
