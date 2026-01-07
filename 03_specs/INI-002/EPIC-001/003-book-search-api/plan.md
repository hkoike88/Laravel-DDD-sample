# Implementation Plan: 蔵書検索API

**Branch**: `003-book-search-api` | **Date**: 2025-12-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-book-search-api/spec.md`

## Summary

蔵書検索APIの実装。フロントエンド開発者が蔵書を検索できるREST APIエンドポイント（`GET /api/books`）を提供する。タイトル・著者名による部分一致検索、ISBNによる完全一致検索、ページネーション機能を実装。既存のBookドメインモデルとリポジトリを活用し、Presentation層とApplication層（UseCase）を追加する。

## Technical Context

**Language/Version**: PHP 8.3 + Laravel 11.x
**Primary Dependencies**: Laravel Framework, Pest（テスト）
**Storage**: MySQL 8.0（既存のbooksテーブル）
**Testing**: Pest（Feature Tests）
**Target Platform**: Docker コンテナ（Linux）
**Project Type**: web（バックエンドAPI）
**Performance Goals**: 1000件の蔵書に対して5秒以内のレスポンス、同時10ユーザー対応
**Constraints**: レスポンス30秒以内、ページサイズ最大100件
**Scale/Scope**: 小規模図書館（蔵書1000件程度、同時10ユーザー）

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution はプレースホルダーテンプレートのため、具体的なゲートチェックは不要。
プロジェクト標準（CLAUDE.md）に従い、以下を遵守：
- [x] DDDアーキテクチャに従った実装
- [x] 日本語でのドキュメント・コメント記述
- [x] テストファースト（Pest）
- [x] 丁寧な作業（品質優先）

## Project Structure

### Documentation (this feature)

```text
specs/003-book-search-api/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── openapi.yaml     # API仕様
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── packages/
│   └── Domain/
│       └── Book/
│           ├── Domain/                    # 既存（変更なし）
│           │   ├── Model/Book.php
│           │   ├── Repositories/BookRepositoryInterface.php
│           │   └── ValueObjects/
│           ├── Application/               # 既存 + 追加
│           │   ├── DTO/
│           │   │   ├── BookSearchCriteria.php  # 既存（ISBN検索条件追加）
│           │   │   └── BookCollection.php       # 既存
│           │   ├── Repositories/
│           │   │   └── EloquentBookRepository.php  # 既存（変更なし）
│           │   ├── Providers/BookServiceProvider.php  # 既存（ルート登録追加）
│           │   └── UseCases/                    # 新規
│           │       └── Queries/
│           │           └── SearchBooks/
│           │               ├── SearchBooksQuery.php
│           │               └── SearchBooksHandler.php
│           ├── Presentation/              # 新規
│           │   └── HTTP/
│           │       ├── BookController.php
│           │       ├── routes.php
│           │       ├── Requests/
│           │       │   └── SearchBooksRequest.php
│           │       └── Resources/
│           │           ├── BookResource.php
│           │           └── BookCollectionResource.php
│           └── Infrastructure/            # 既存（変更なし）
│               └── EloquentModels/BookRecord.php
└── tests/
    └── Feature/
        └── Book/
            └── SearchBooksTest.php        # 新規
```

**Structure Decision**: 既存のDDDパッケージ構造（`packages/Domain/Book/`）を拡張。Presentation層とUseCase（Query）を追加。

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A | 既存構造を活用 | - |
