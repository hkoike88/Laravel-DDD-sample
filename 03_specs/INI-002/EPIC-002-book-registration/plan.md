# Implementation Plan: 蔵書登録

**Branch**: `001-book-registration` | **Date**: 2026-01-06 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-book-registration/spec.md`

## Summary

職員が新規購入した図書や寄贈図書をシステムに登録する機能を実装する。既存の Book エンティティとリポジトリを拡張し、登録者情報の記録、ISBN重複チェックAPI、フロントエンド登録画面を新規実装する。

## Technical Context

**Language/Version**: PHP 8.3 (Backend), TypeScript 5.7 (Frontend)
**Primary Dependencies**: Laravel 11.x, React 18.x, TanStack Query 5.x, React Hook Form 7.x, Zod 3.x
**Storage**: MySQL 8.0 (既存 books テーブルを拡張)
**Testing**: Pest (Backend), Vitest (Frontend)
**Target Platform**: Web Application (SPA + REST API)
**Project Type**: Web Application (frontend + backend)
**Performance Goals**: 蔵書登録 1件/分以内、検索反映 3秒以内
**Constraints**: 認証済み職員のみアクセス可能、ISBN重複時はリアルタイム警告
**Scale/Scope**: 小規模図書館（蔵書数 10,000冊、利用者数 500名程度）

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution が未設定のため、プロジェクトの設計標準に従う:
- [x] DDD アーキテクチャに準拠（`00_docs/20_tech/20_architecture/backend/`）
- [x] Feature-based アーキテクチャに準拠（Frontend）
- [x] API 設計標準に準拠（`00_docs/20_tech/99_standard/backend/05_ApiDesign.md`）
- [x] バリデーション設計標準に準拠（`00_docs/20_tech/99_standard/backend/08_ValidationDesign.md`）

## Project Structure

### Documentation (this feature)

```text
specs/001-book-registration/
├── plan.md              # This file
├── research.md          # Phase 0 output - 既存実装分析と技術決定
├── data-model.md        # Phase 1 output - エンティティとDB設計
├── quickstart.md        # Phase 1 output - 実装手順ガイド
├── contracts/           # Phase 1 output - API仕様
│   └── openapi.yaml     # OpenAPI 3.0 仕様
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
backend/
├── database/migrations/
│   └── 2026_01_06_000000_add_registration_columns_to_books_table.php
└── packages/Domain/Book/
    ├── Application/
    │   ├── UseCases/Commands/CreateBook/
    │   │   ├── CreateBookCommand.php       # MODIFY
    │   │   └── CreateBookHandler.php       # MODIFY
    │   └── Repositories/
    │       └── EloquentBookRepository.php  # (既存)
    ├── Domain/
    │   ├── Model/
    │   │   └── Book.php                    # MODIFY
    │   └── Repositories/
    │       └── BookRepositoryInterface.php # (既存)
    ├── Infrastructure/EloquentModels/
    │   └── BookRecord.php                  # MODIFY
    └── Presentation/HTTP/
        ├── Controllers/
        │   └── BookController.php          # MODIFY
        ├── Requests/
        │   ├── CreateBookRequest.php       # MODIFY
        │   └── CheckIsbnRequest.php        # NEW
        ├── Resources/
        │   └── BookResource.php            # MODIFY
        └── routes.php                      # MODIFY

frontend/src/features/books/
├── api/
│   └── bookApi.ts                          # MODIFY
├── components/
│   ├── BookRegistrationForm.tsx            # NEW
│   └── IsbnDuplicateWarning.tsx            # NEW
├── hooks/
│   ├── useBookRegistration.ts              # NEW
│   └── useIsbnCheck.ts                     # NEW
├── pages/
│   ├── BookRegistrationPage.tsx            # NEW
│   └── BookCompletePage.tsx                # NEW
├── schemas/
│   └── bookRegistration.ts                 # NEW
└── types/
    └── book.ts                             # MODIFY
```

**Structure Decision**: 既存の Web Application 構造を維持し、Book ドメインの拡張と Frontend books feature への追加を行う。

## Implementation Summary

### 既存実装（変更なし）

- Book エンティティ基本構造
- BookRepositoryInterface と EloquentBookRepository
- ISBN Value Object とバリデーション
- 蔵書検索機能（フロントエンド・バックエンド）

### 新規実装・拡張

| カテゴリ | 項目 | 優先度 |
|---------|------|--------|
| DB | `registered_by`, `registered_at` カラム追加 | P1 |
| Backend | CreateBookRequest バリデーション調整 | P1 |
| Backend | CreateBookHandler 登録者情報記録 | P1 |
| Backend | 認証ガード追加 | P1 |
| Backend | ISBN重複チェックAPI | P2 |
| Frontend | 登録フォームコンポーネント | P1 |
| Frontend | ISBN リアルタイムチェック | P2 |
| Frontend | 登録確認画面 | P2 |
| Test | バックエンドテスト | P1-P2 |
| Test | フロントエンドテスト | P1-P2 |

### バリデーションルール変更

| 項目 | 現状 | 仕様要件 | 対応 |
|------|------|---------|------|
| タイトル最大長 | 500文字 | 200文字 | 修正 |
| 著者名最大長 | 200文字 | 100文字 | 修正 |
| 出版社最大長 | 200文字 | 100文字 | 修正 |
| 出版年範囲 | 1〜現在年+5 | 1000〜現在年+1 | 修正 |

## API Endpoints

| メソッド | パス | 説明 | 認証 |
|---------|------|------|------|
| POST | `/api/books` | 蔵書登録（既存拡張） | 必須 |
| GET | `/api/books/check-isbn` | ISBN重複チェック（新規） | 必須 |
| GET | `/api/books/{id}` | 蔵書詳細取得（既存） | 不要 |

## Related Documents

- [research.md](./research.md) - 既存実装分析と技術決定
- [data-model.md](./data-model.md) - エンティティとデータベース設計
- [quickstart.md](./quickstart.md) - 実装手順ガイド
- [contracts/openapi.yaml](./contracts/openapi.yaml) - API仕様（OpenAPI 3.0）

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

該当なし - 既存アーキテクチャの拡張のみで対応可能
