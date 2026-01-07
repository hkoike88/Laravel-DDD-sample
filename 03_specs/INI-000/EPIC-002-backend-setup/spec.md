# Feature Specification: バックエンド初期設定

**Feature Branch**: `003-backend-setup`
**Created**: 2025-12-23
**Status**: Draft
**Input**: EPIC-002 バックエンド初期設定

## Overview

開発チームが Laravel + DDD（ドメイン駆動設計）アーキテクチャで開発を開始できるバックエンド基盤を整備する。プロジェクト作成、ディレクトリ構成、静的解析ツール、テストフレームワークを含む完全な開発環境を構築する。

## User Scenarios & Testing

### User Story 1 - Laravel プロジェクトの作成と基本動作確認 (Priority: P1) 🎯 MVP

開発者として、Laravel プロジェクトを Docker コンテナ内で作成し、基本的なコマンドが実行できる状態にしたい。

**Why this priority**: すべての後続作業の基盤となるため、最優先で完了が必要。

**Independent Test**: `php artisan` コマンドが正常に実行でき、Laravel のウェルカムページが表示される。

**Acceptance Scenarios**:

1. **Given** Docker 環境が起動している状態で、**When** backend コンテナ内で Laravel がインストールされている、**Then** `php artisan --version` が Laravel バージョンを表示する
2. **Given** Laravel プロジェクトが作成されている状態で、**When** ブラウザで API エンドポイントにアクセスする、**Then** 正常なレスポンスが返される
3. **Given** Laravel プロジェクトが存在する状態で、**When** `php artisan list` を実行する、**Then** 利用可能なコマンド一覧が表示される

---

### User Story 2 - DDD ディレクトリ構成の作成 (Priority: P1)

開発者として、DDD アーキテクチャに基づいたディレクトリ構成が整備されていることで、ドメイン駆動設計のパターンに従ったコード配置が可能になる。

**Why this priority**: 開発開始前に構造が決まっていることで、チーム全体が一貫した設計方針でコードを書ける。

**Independent Test**: 所定のディレクトリ構成が存在し、オートローダーが正しく設定されている。

**Acceptance Scenarios**:

1. **Given** Laravel プロジェクトが存在する状態で、**When** `app/src/` ディレクトリを確認する、**Then** DDD 構成（Domain, Application, Presentation, Infrastructure）のディレクトリが存在する
2. **Given** DDD ディレクトリ構成が作成されている状態で、**When** Composer オートローダーを実行する、**Then** 新しい名前空間が正しく解決される
3. **Given** Common ディレクトリが存在する状態で、**When** 共有リソースを参照する、**Then** 他のコンテキストから正しくアクセスできる

---

### User Story 3 - データベース接続の確認 (Priority: P1)

開発者として、Laravel から MySQL データベースへの接続が正常に動作することを確認したい。

**Why this priority**: データベース接続は多くの機能の前提条件となる。

**Independent Test**: `php artisan migrate` コマンドが正常に実行できる。

**Acceptance Scenarios**:

1. **Given** Docker 環境で MySQL が起動している状態で、**When** `php artisan migrate` を実行する、**Then** マイグレーションが正常に完了する
2. **Given** データベース接続が設定されている状態で、**When** `php artisan db:show` を実行する、**Then** 接続情報が正しく表示される
3. **Given** マイグレーションが実行された状態で、**When** データベースのテーブル一覧を確認する、**Then** Laravel 標準テーブルが作成されている

---

### User Story 4 - 静的解析ツール（PHPStan）の設定 (Priority: P2)

開発者として、PHPStan/Larastan による静的解析が実行できる環境を整備し、コード品質を維持したい。

**Why this priority**: 開発開始後のコード品質維持に必要だが、初期動作確認より後でよい。

**Independent Test**: `./vendor/bin/phpstan analyse` コマンドがエラーなく完了する。

**Acceptance Scenarios**:

1. **Given** Larastan がインストールされている状態で、**When** `./vendor/bin/phpstan analyse` を実行する、**Then** 解析が正常に完了する
2. **Given** PHPStan 設定ファイルが存在する状態で、**When** 設定を確認する、**Then** 解析レベルと対象パスが適切に設定されている
3. **Given** 意図的にエラーを含むコードがある状態で、**When** 静的解析を実行する、**Then** エラーが検出・報告される

---

### User Story 5 - テスト環境（Pest）の設定 (Priority: P2)

開発者として、Pest テストフレームワークが動作する環境を整備し、TDD でコードを書けるようにしたい。

**Why this priority**: テスト駆動開発の基盤として必要だが、基本設定完了後でよい。

**Independent Test**: `./vendor/bin/pest` コマンドがサンプルテストを実行できる。

**Acceptance Scenarios**:

1. **Given** Pest がインストールされている状態で、**When** `./vendor/bin/pest` を実行する、**Then** テストが正常に実行される
2. **Given** テストディレクトリが存在する状態で、**When** 新しいテストファイルを作成する、**Then** Pest がテストを認識・実行する
3. **Given** サンプルテストが存在する状態で、**When** `./vendor/bin/pest --coverage` を実行する、**Then** カバレッジレポートが生成される

---

### User Story 6 - 認証パッケージ（Sanctum）の導入 (Priority: P3)

開発者として、API 認証の基盤となる Laravel Sanctum がインストールされた状態にしたい。

**Why this priority**: 認証機能は初期設定の一部だが、他の基盤設定完了後でよい。

**Independent Test**: Sanctum の設定ファイルが存在し、ミドルウェアが登録されている。

**Acceptance Scenarios**:

1. **Given** Sanctum がインストールされている状態で、**When** 設定ファイルを確認する、**Then** `config/sanctum.php` が存在する
2. **Given** Sanctum マイグレーションが実行された状態で、**When** データベースを確認する、**Then** `personal_access_tokens` テーブルが存在する

---

### Edge Cases

- Laravel プロジェクト作成時に既存ファイルが存在する場合はどうなるか？ → 既存の composer.json を尊重し、必要なパッケージのみ追加
- Composer 依存解決に失敗した場合はどうなるか？ → エラーメッセージを明確に表示し、手動解決の手順を提供
- PHPStan でレベルが高すぎてエラーが多発する場合はどうなるか？ → レベル 5 から開始し、段階的に上げる方針
- Docker コンテナ外からコマンドを実行しようとした場合はどうなるか？ → `docker compose exec` 経由での実行を案内

## Requirements

### Functional Requirements

- **FR-001**: システムは Laravel 11.x フレームワークをベースとして動作すること
- **FR-002**: システムは DDD アーキテクチャに基づいたディレクトリ構成（Domain, Application, Presentation, Infrastructure）を持つこと
- **FR-003**: 開発者は `php artisan` コマンドで Laravel の各種操作を実行できること
- **FR-004**: システムは MySQL データベースに接続し、マイグレーションを実行できること
- **FR-005**: システムは PHPStan/Larastan による静的解析を実行できること
- **FR-006**: システムは Pest によるユニットテスト・機能テストを実行できること
- **FR-007**: システムは Laravel Sanctum による API 認証機構を備えること
- **FR-008**: Composer オートローダーは DDD ディレクトリ構成の名前空間を正しく解決すること
- **FR-009**: 環境設定は `.env` ファイルで管理され、Docker 環境と連携すること
- **FR-010**: テストは本番データベースとは分離された環境で実行されること

### Key Entities

- **Bounded Context**: ドメインを分離する単位（BookManagement, LoanManagement, UserManagement など）
- **Domain Layer**: ビジネスロジックとドメインモデルを含む層
- **Application Layer**: ユースケースとアプリケーションサービスを含む層
- **Infrastructure Layer**: 外部システムとの接続・永続化を担当する層
- **Presentation Layer**: API エンドポイントとリクエスト/レスポンス処理を担当する層

## Success Criteria

### Measurable Outcomes

- **SC-001**: `php artisan --version` コマンドが Laravel 11.x のバージョンを表示する
- **SC-002**: `php artisan migrate` コマンドが 30 秒以内に正常完了する
- **SC-003**: `./vendor/bin/phpstan analyse` コマンドがエラー 0 件で完了する
- **SC-004**: `./vendor/bin/pest` コマンドがサンプルテストをパスする
- **SC-005**: DDD ディレクトリ構成（app/src/ 配下）が仕様通りに作成されている
- **SC-006**: Composer オートローダーが新しい名前空間を正しく解決する
- **SC-007**: 開発者は 5 分以内にバックエンド環境をセットアップできる

## Assumptions

- Docker 環境（EPIC-001）が正常に動作していること
- PHP 8.3 が Docker コンテナ内で利用可能であること
- インターネット接続があり、Composer パッケージのダウンロードが可能であること
- 開発者は基本的な Laravel の知識を持っていること

## Dependencies

| Dependency | Type | Description |
|------------|------|-------------|
| EPIC-001 Docker 環境構築 | 前提 | Docker Compose 環境が動作していること |
| MySQL 8.0 | 外部 | データベースサーバーが起動していること |

## Out of Scope

- 具体的なドメインモデルの実装（書籍、貸出、ユーザーなど）
- API エンドポイントの実装
- フロントエンドとの連携
- 本番環境へのデプロイ設定
- CI/CD パイプラインの構築
