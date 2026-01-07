# Implementation Plan: 職員アカウント作成機能

**Branch**: `007-staff-account-create` | **Date**: 2026-01-06 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/007-staff-account-create/spec.md`

## Summary

管理者が新しい職員アカウントを作成する機能を実装する。職員の基本情報（氏名、メールアドレス）と権限（一般職員/管理者）を設定し、16文字のランダムな初期パスワードを自動生成する。作成完了後は作成結果画面で初期パスワードを確認でき、「一覧へ戻る」ボタンで職員一覧画面に遷移する。職員一覧は20件/ページのページネーション形式で表示し、すべての操作は監査ログに記録される。

## Technical Context

**Language/Version**: PHP 8.2+ (Backend), TypeScript 5.3+ (Frontend)
**Primary Dependencies**: Laravel 12.x, Laravel Sanctum 4.x, React 18.x, TanStack Query 5.x, Zustand 5.x, React Hook Form 7.x, Zod 4.x, Tailwind CSS 3.x
**Storage**: MySQL 8.0（既存の staffs テーブルを使用）
**Testing**: Pest (Backend), Vitest (Frontend)
**Target Platform**: Web（Docker 環境）
**Project Type**: Web application (frontend + backend)
**Performance Goals**: アカウント作成は3秒以内、バリデーションエラー表示は1秒以内
**Constraints**: 管理者のみがアクセス可能、メールアドレスは一意、初期パスワードは16文字
**Scale/Scope**: 職員数は数十〜数百名程度を想定、ページネーション20件/ページ

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution ファイルはテンプレート状態のため、明確なゲートは定義されていません。
既存のプロジェクトアーキテクチャ（DDD パターン、packages 構造）に従って実装します。

**既存のアーキテクチャパターン**:
- Domain/Staff ドメイン（既存）: Staff エンティティ、StaffRepositoryInterface
- Application 層: UseCases (Commands/Queries)、DTO
- Infrastructure 層: EloquentStaffRepository
- Presentation 層: Controllers（packages/.../Presentation/HTTP/Controllers/）

## Project Structure

### Documentation (this feature)

```text
specs/007-staff-account-create/
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
│   └── Http/
│       └── Middleware/
│           └── EnsureUserIsAdmin.php             # 管理者権限チェック（既存または新規）
├── packages/
│   └── Domain/
│       └── Staff/
│           ├── Application/
│           │   ├── DTO/
│           │   │   └── StaffAccount/
│           │   │       ├── CreateStaffInput.php
│           │   │       ├── CreateStaffOutput.php
│           │   │       └── StaffListOutput.php
│           │   └── UseCases/
│           │       ├── Commands/
│           │       │   └── CreateStaff/
│           │       │       ├── CreateStaffCommand.php
│           │       │       └── CreateStaffHandler.php
│           │       └── Queries/
│           │           └── GetStaffList/
│           │               ├── GetStaffListQuery.php
│           │               └── GetStaffListHandler.php
│           ├── Domain/
│           │   └── Services/
│           │       └── PasswordGenerator.php     # 初期パスワード生成
│           ├── Presentation/
│           │   └── HTTP/
│           │       ├── Controllers/
│           │       │   └── StaffAccountController.php    # 職員管理 API
│           │       └── Requests/
│           │           └── CreateStaffRequest.php        # バリデーション
│           └── Infrastructure/
│               └── AuditLog/
│                   └── StaffAuditLogger.php      # 監査ログ記録
├── routes/
│   └── api.php                                   # API ルート追加
└── tests/
    ├── Unit/
    │   └── Packages/
    │       └── Domain/
    │           └── Staff/
    │               └── Services/
    │                   └── PasswordGeneratorTest.php
    └── Feature/
        └── Http/
            └── Controllers/
                └── Staff/
                    └── StaffAccountControllerTest.php

frontend/
├── src/
│   ├── pages/
│   │   └── staff/
│   │       ├── StaffAccountsListPage.tsx         # 職員一覧画面
│   │       ├── StaffAccountsNewPage.tsx          # 職員作成画面
│   │       └── StaffAccountsResultPage.tsx       # 作成結果画面
│   ├── features/
│   │   └── staff-accounts/
│   │       ├── api/
│   │       │   └── staffAccountsApi.ts           # API 呼び出し
│   │       ├── components/
│   │       │   ├── StaffCreateForm.tsx           # 作成フォーム
│   │       │   ├── StaffListTable.tsx            # 一覧テーブル
│   │       │   ├── PasswordDisplay.tsx           # パスワード表示コンポーネント
│   │       │   └── Pagination.tsx                # ページネーション
│   │       ├── hooks/
│   │       │   ├── useCreateStaff.ts
│   │       │   └── useStaffList.ts
│   │       ├── types/
│   │       │   └── staffAccount.ts               # 型定義
│   │       └── schemas/
│   │           └── createStaffSchema.ts          # Zod スキーマ
│   └── routes/
│       └── index.tsx                             # ルート追加
└── tests/
    └── features/
        └── staff-accounts/
            └── StaffCreateForm.test.tsx
```

**Structure Decision**: Option 2（Web application）を選択。既存のプロジェクト構造（backend/packages/Domain/Staff/）を拡張し、フロントエンドは features ベースの構造で実装する。

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

Constitution が未設定のため、違反はありません。既存のアーキテクチャパターンに従います。
