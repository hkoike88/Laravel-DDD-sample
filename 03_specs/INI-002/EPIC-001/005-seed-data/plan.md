# Implementation Plan: シードデータ投入

**Branch**: `005-seed-data` | **Date**: 2025-12-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-seed-data/spec.md`

## Summary

開発者が蔵書データをデータベースに投入するための3つの機能を実装する：
1. **P1**: 事前定義されたサンプルデータ（100件以上）をコマンド一つで投入
2. **P2**: CSVファイルからのデータインポート（バリデーション付き）
3. **P3**: ランダムなテストデータの生成（Factory使用）

Laravelの標準的なSeeder/Factory機構とArtisanコマンドを活用して実装する。

## Technical Context

**Language/Version**: PHP 8.3
**Primary Dependencies**: Laravel 11.x, symfony/uid（ULID生成）
**Storage**: MySQL 8.0（既存のbooksテーブル）
**Testing**: Pest
**Target Platform**: Docker コンテナ（開発/テスト環境）
**Project Type**: Web application（backend）
**Performance Goals**: 1000件のCSVインポートを1分以内に処理
**Constraints**: 開発/テスト環境専用、本番環境での使用は想定外
**Scale/Scope**: 100〜10,000件程度のデータ投入

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution はテンプレート状態のため、特定のゲートなし。
標準的なLaravelの規約に従って実装を進める。

## Project Structure

### Documentation (this feature)

```text
specs/005-seed-data/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output (CLI command specs)
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── database/
│   ├── seeders/
│   │   ├── DatabaseSeeder.php    # 既存
│   │   └── BookSeeder.php        # 新規: サンプルデータ投入
│   └── factories/
│       ├── UserFactory.php       # 既存
│       └── BookFactory.php       # 新規: ランダムデータ生成
├── app/
│   └── Console/
│       └── Commands/
│           └── ImportBooksCommand.php  # 新規: CSVインポート
├── storage/
│   └── app/
│       └── sample_books.csv      # 新規: サンプルCSVファイル
└── tests/
    ├── Feature/
    │   └── Seed/
    │       ├── BookSeederTest.php
    │       └── ImportBooksCommandTest.php
    └── Unit/
        └── Domain/
            └── Book/
                └── BookFactoryTest.php
```

**Structure Decision**: 既存のLaravelプロジェクト構造に従い、database/seeders, database/factories, app/Console/Commands に各ファイルを配置する。

## Complexity Tracking

> 違反なし - 標準的なLaravelの機能を使用するため追加の複雑さはない
