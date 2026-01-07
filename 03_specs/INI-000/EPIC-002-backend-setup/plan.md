# Implementation Plan: バックエンド初期設定

**Branch**: `003-backend-setup` | **Date**: 2025-12-23 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-backend-setup/spec.md`

## Summary

Laravel 11.x フレームワークを Docker コンテナ内にセットアップし、DDD（ドメイン駆動設計）アーキテクチャに基づいたディレクトリ構成を構築する。静的解析（PHPStan/Larastan）、テストフレームワーク（Pest）、API 認証（Sanctum）を導入し、開発チームが一貫した設計方針でコードを書ける基盤を整備する。

## Technical Context

**Language/Version**: PHP 8.3 (php:8.3-fpm Docker イメージ)
**Primary Dependencies**: Laravel 11.x, Larastan, Pest, Laravel Sanctum
**Storage**: MySQL 8.0 (Docker コンテナ)
**Testing**: Pest (PHPUnit 互換)
**Target Platform**: Docker コンテナ (Linux)
**Project Type**: Web application (backend API)
**Performance Goals**: `php artisan migrate` 30秒以内完了、開発者は5分以内にセットアップ完了
**Constraints**: Docker 環境必須、EPIC-001 完了前提
**Scale/Scope**: 開発チーム向け初期設定、本番デプロイは対象外

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution は現在テンプレート状態のため、以下の暗黙的なプリンシプルを適用：

| Gate | Status | Note |
|------|--------|------|
| DDD アーキテクチャ準拠 | ✅ PASS | 4層構成（Domain, Application, Infrastructure, Presentation）を採用 |
| テスト環境整備 | ✅ PASS | Pest テストフレームワークを導入 |
| 静的解析ツール | ✅ PASS | PHPStan/Larastan を導入 |
| Docker 統合 | ✅ PASS | 既存の php:8.3-fpm コンテナを活用 |

## Project Structure

### Documentation (this feature)

```text
specs/003-backend-setup/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── src/                          # DDD ソースコード
│   │   ├── Common/                   # 共有リソース
│   │   │   ├── Domain/
│   │   │   ├── Application/
│   │   │   └── Infrastructure/
│   │   ├── BookManagement/           # 書籍管理コンテキスト（将来）
│   │   │   ├── Domain/
│   │   │   ├── Application/
│   │   │   ├── Infrastructure/
│   │   │   └── Presentation/
│   │   ├── LoanManagement/           # 貸出管理コンテキスト（将来）
│   │   └── UserManagement/           # ユーザー管理コンテキスト（将来）
│   ├── Http/                         # Laravel 標準（コントローラー等）
│   ├── Models/                       # Eloquent モデル
│   └── Providers/
├── config/
├── database/
│   └── migrations/
├── public/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
├── phpstan.neon
└── phpunit.xml

frontend/
├── src/
│   ├── components/
│   ├── pages/
│   └── services/
└── tests/
```

**Structure Decision**: Web application 構造を採用。バックエンド（Laravel + DDD）とフロントエンド（React）を分離。DDD 層は `app/src/` 配下に Bounded Context ごとに配置。

## Complexity Tracking

> **現時点では Constitution 違反なし**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A | - | - |
