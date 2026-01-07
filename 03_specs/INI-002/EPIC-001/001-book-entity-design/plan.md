# Implementation Plan: 蔵書エンティティ・Value Object 設計

**Branch**: `001-book-entity-design` | **Date**: 2025-12-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-book-entity-design/spec.md`

## Summary

蔵書（Book）ドメインモデルの設計と実装。DDDに基づいたドメイン層を構築し、Book エンティティ、BookId/ISBN/BookStatus の Value Object を作成する。ビジネスルール（ISBN検証、状態遷移）をドメインモデルにカプセル化し、単体テストで検証可能にする。

## Technical Context

**Language/Version**: PHP 8.3
**Primary Dependencies**: Laravel 11.x, symfony/uid（ULID生成）, Pest（テスト）
**Storage**: MySQL 8.0（本フィーチャーではリポジトリインターフェースのみ定義、実装は後続）
**Testing**: Pest（Unit テスト）
**Target Platform**: Linux server（Docker コンテナ）
**Project Type**: Web application（backend + frontend）
**Performance Goals**: 蔵書登録・検索が3秒以内、単体テストカバレッジ100%
**Constraints**: DDDドメイン別グループ化アーキテクチャに準拠
**Scale/Scope**: 初期は蔵書ドメインのみ、将来的にユーザー・貸出ドメインと連携

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

コンスティテューションがテンプレート状態のため、明示的なゲートは定義されていません。
プロジェクト標準ドキュメント（00_docs/20_tech/99_standard/backend/）に従って設計を進めます。

**適用する原則:**
- [x] DDD ドメイン別グループ化アプローチ（00_概要.md）
- [x] Domain Model と Eloquent Model の分離（02_実装パターン.md）
- [x] ULID による ID 生成（ADR-0006）
- [x] テストピラミッド: Unit テスト多数、Domain 層 90%+ カバレッジ（04_テスト戦略.md）

## Project Structure

### Documentation (this feature)

```text
specs/001-book-entity-design/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output（本フィーチャーでは N/A - API なし）
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
backend/
├── packages/                        # DDD ドメインパッケージ
│   └── Domain/
│       └── Book/                    # 蔵書ドメイン（境界づけられたコンテキスト省略）
│           ├── Domain/              # ドメイン層
│           │   ├── Model/
│           │   │   └── Book.php     # エンティティ
│           │   ├── ValueObjects/
│           │   │   ├── BookId.php
│           │   │   ├── ISBN.php
│           │   │   └── BookStatus.php
│           │   ├── Repositories/
│           │   │   └── BookRepositoryInterface.php
│           │   └── Exceptions/
│           │       ├── InvalidISBNException.php
│           │       └── InvalidBookStatusTransitionException.php
│           └── Application/         # アプリケーション層（後続タスク）
│               └── Providers/
│                   └── BookServiceProvider.php
└── tests/
    └── Unit/
        └── Domain/
            └── Book/
                ├── Model/
                │   └── BookTest.php
                └── ValueObjects/
                    ├── BookIdTest.php
                    ├── ISBNTest.php
                    └── BookStatusTest.php
```

**Structure Decision**: ドメイン別グループ化アプローチを採用。小規模プロジェクトのため、境界づけられたコンテキスト（BoundedContext）層は省略し、`packages/Domain/Book/` 直下にドメインを配置する。将来的にコンテキストが増えた場合は `packages/{BoundedContext}/{Domain}/` 構造に移行可能。

## Complexity Tracking

該当なし（コンスティテューション違反なし）
