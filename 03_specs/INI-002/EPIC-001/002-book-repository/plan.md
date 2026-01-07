# Implementation Plan: 蔵書リポジトリ実装

**Branch**: `002-book-repository` | **Date**: 2025-12-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-book-repository/spec.md`

## Summary

蔵書エンティティの永続化機能を実装する。001-book-entity-design で作成した Book、BookId、ISBN、BookStatus の Value Object を MySQL データベースに永続化し、ID 検索、ISBN 検索、条件検索、ページネーション、ソート機能を提供する。DDD のリポジトリパターンに従い、ドメイン層とインフラストラクチャ層を分離した設計とする。

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12.x
**Primary Dependencies**: Laravel Eloquent ORM, symfony/uid (ULID)
**Storage**: MySQL 8.0 (Docker コンテナ, Named Volume: db_data)
**Testing**: Pest 3.x (単体テスト + 統合テスト)
**Target Platform**: Linux server (Docker コンテナ)
**Project Type**: web (backend + frontend 構成)
**Performance Goals**:
- 蔵書保存: 1秒以内
- ID 検索: 100ms 以内
- 条件検索（1万件DB）: 1秒以内
**Constraints**:
- 同時 100 件保存で競合なし
- タイトル 255 文字上限
- ページサイズ最大 100 件
**Scale/Scope**: 10 万件以上の蔵書データを想定

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

このプロジェクトでは constitution.md がテンプレート状態のため、以下の標準に従う:

- **DDD アーキテクチャ**: 00_docs/20_tech/99_standard/backend/01_ArchitectureDesign/ に準拠
- **リポジトリパターン**: Domain 層にインターフェース、Application 層に実装
- **テスト戦略**: Unit テスト（Domain）+ Integration テスト（Repository）
- **コーディング規約**: 00_docs/20_tech/99_standard/backend/02_CodingStandards.md に準拠

## Project Structure

### Documentation (this feature)

```text
specs/002-book-repository/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── packages/Domain/Book/
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── Book.php                    # 既存 - エンティティ
│   │   ├── ValueObjects/
│   │   │   ├── BookId.php                  # 既存
│   │   │   ├── ISBN.php                    # 既存
│   │   │   └── BookStatus.php              # 既存
│   │   ├── Repositories/
│   │   │   └── BookRepositoryInterface.php # 既存 - 拡張が必要
│   │   └── Exceptions/
│   │       ├── BookNotFoundException.php   # 新規
│   │       └── ...                         # 既存
│   ├── Application/
│   │   ├── DTO/
│   │   │   ├── BookSearchCriteria.php      # 新規 - 検索条件
│   │   │   └── BookCollection.php          # 新規 - 検索結果
│   │   ├── Repositories/
│   │   │   └── EloquentBookRepository.php  # 新規 - リポジトリ実装
│   │   └── Providers/
│   │       └── BookServiceProvider.php     # 既存 - バインディング追加
│   └── Infrastructure/
│       └── EloquentModels/
│           └── BookRecord.php              # 新規 - Eloquent モデル
├── database/
│   └── migrations/
│       └── xxxx_create_books_table.php     # 新規 - マイグレーション
└── tests/
    ├── Unit/Domain/Book/
    │   └── DTO/
    │       ├── BookSearchCriteriaTest.php  # 新規
    │       └── BookCollectionTest.php      # 新規
    └── Integration/Domain/Book/
        └── Repositories/
            └── EloquentBookRepositoryTest.php  # 新規
```

**Structure Decision**: 既存の `backend/packages/Domain/Book/` 構造を拡張。DDD のドメイン別グループ化パターンに従い、Application 層にリポジトリ実装、Infrastructure 層に Eloquent モデルを配置。

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| なし | - | - |
