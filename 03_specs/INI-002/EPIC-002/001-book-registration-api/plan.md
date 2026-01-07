# Implementation Plan: 蔵書登録API実装

**Branch**: `001-book-registration-api` | **Date**: 2025-12-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-book-registration-api/spec.md`

## Summary

図書館職員が新規購入した図書をシステムに登録するためのAPIを実装する。既存のBookエンティティとリポジトリを活用し、CreateBookUseCaseを新規作成してPOST /api/booksエンドポイントを提供する。タイトル必須、ISBN/著者名/出版社/出版年/ジャンルはオプション。バリデーションエラー時は422、成功時は201を返す。

## Technical Context

**Language/Version**: PHP 8.3 + Laravel 11.x
**Primary Dependencies**: Laravel Framework, symfony/uid（ULID生成）, Pest（テスト）
**Storage**: MySQL 8.0（既存のbooksテーブル）
**Testing**: Pest（Feature/Unitテスト）
**Target Platform**: Docker コンテナ（Linux）
**Project Type**: Web application（Backend API + Frontend React）
**Performance Goals**: 登録処理3秒以内（SC-001: 1分以内に登録完了）
**Constraints**: バリデーション100%準拠（SC-005）、認証は別機能（Laravel Sanctum）
**Scale/Scope**: 1日100冊以上の登録処理（SC-004）

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| 原則 | ステータス | 備考 |
|------|------------|------|
| DDD構造 | ✅ Pass | 既存のBookドメイン構造に従う |
| テストファースト | ✅ Pass | Pestでテスト作成後に実装 |
| ドキュメント記載 | ✅ Pass | PHPDocコメント記載 |
| 日本語対応 | ✅ Pass | エラーメッセージ・コメント日本語 |

## Project Structure

### Documentation (this feature)

```text
specs/001-book-registration-api/
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
│   │   ├── Model/Book.php                    # 既存エンティティ
│   │   ├── ValueObjects/                     # 既存VO（BookId, ISBN, BookStatus）
│   │   ├── Exceptions/                       # 既存例外
│   │   └── Repositories/BookRepositoryInterface.php  # 既存インターフェース
│   ├── Application/
│   │   ├── UseCases/
│   │   │   ├── Queries/SearchBooks/          # 既存（検索）
│   │   │   └── Commands/CreateBook/          # 新規作成
│   │   │       ├── CreateBookCommand.php
│   │   │       └── CreateBookHandler.php
│   │   ├── DTO/
│   │   │   └── CreateBookInput.php           # 新規作成
│   │   └── Repositories/EloquentBookRepository.php  # 既存（save()メソッド実装済み）
│   └── Presentation/
│       └── HTTP/
│           ├── Controllers/BookController.php    # store()メソッド追加
│           ├── Requests/CreateBookRequest.php    # 新規作成
│           └── Resources/BookResource.php        # 既存
└── tests/
    ├── Feature/Book/
    │   └── CreateBookTest.php                # 新規作成
    └── Unit/Domain/Book/
        └── UseCases/CreateBookHandlerTest.php  # 新規作成
```

**Structure Decision**: 既存のDDDレイヤー構造（Domain/Application/Presentation/Infrastructure）に従い、CreateBook関連のコマンド・ハンドラー・リクエストを追加。既存のBookエンティティとリポジトリを再利用。

## Complexity Tracking

> 違反なし - 既存アーキテクチャに準拠

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A | - | - |
