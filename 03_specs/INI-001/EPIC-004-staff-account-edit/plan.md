# Implementation Plan: 職員アカウント編集機能

**Branch**: `001-epic-004-staff-account-edit` | **Date**: 2026-01-06 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-epic-004-staff-account-edit/spec.md`

## Summary

管理者が既存の職員アカウント情報（氏名、メールアドレス、権限）を編集し、パスワードをリセットできる機能を実装する。楽観的ロックによる同時編集の競合検出、自己権限変更の防止、最後の管理者保護、監査ログ記録を含む。EPIC-003で実装済みの職員作成機能のアーキテクチャパターンを踏襲する。

## Technical Context

**Language/Version**: PHP 8.3 (Backend), TypeScript 5.3 (Frontend)
**Primary Dependencies**: Laravel 11.x, Laravel Sanctum 4.x, React 18.x, TanStack Query 5.x, Zustand 5.x, React Hook Form 7.x, Zod 4.x, Tailwind CSS 3.x
**Storage**: MySQL 8.0（既存の staffs テーブルを使用）
**Testing**: Pest (Backend), Vitest (Frontend)
**Target Platform**: Web Application (Docker containers)
**Project Type**: Web Application (Backend + Frontend)
**Performance Goals**: 更新処理3秒以内、パスワードリセット2秒以内
**Constraints**: 管理者権限必須、楽観的ロック必須
**Scale/Scope**: 既存職員データの編集機能

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

プロジェクトのconstitution.mdはテンプレート状態のため、以下のプロジェクト標準を適用：

- [x] DDDアーキテクチャに準拠（Domain/Application/Infrastructure/Presentation層）
- [x] 既存のEPIC-003パターンを踏襲
- [x] セキュリティ標準に準拠（パスワードハッシュ化、監査ログ）
- [x] APIドキュメントガイドラインに準拠
- [x] コーディング規約に準拠（PHPStan, Pint, ESLint, Prettier）

## Project Structure

### Documentation (this feature)

```text
specs/001-epic-004-staff-account-edit/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── openapi.yaml
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── packages/Domain/Staff/
│   ├── Application/
│   │   ├── DTO/StaffAccount/
│   │   │   ├── UpdateStaffInput.php          # NEW
│   │   │   ├── UpdateStaffOutput.php         # NEW
│   │   │   └── ResetPasswordOutput.php       # NEW
│   │   └── UseCases/
│   │       ├── Commands/
│   │       │   ├── UpdateStaff/
│   │       │   │   ├── UpdateStaffCommand.php    # NEW
│   │       │   │   └── UpdateStaffHandler.php    # NEW
│   │       │   └── ResetPassword/
│   │       │       ├── ResetPasswordCommand.php  # NEW
│   │       │       └── ResetPasswordHandler.php  # NEW
│   │       └── Queries/
│   │           └── GetStaffDetail/
│   │               ├── GetStaffDetailQuery.php   # NEW
│   │               └── GetStaffDetailHandler.php # NEW
│   ├── Domain/
│   │   ├── Exceptions/
│   │   │   ├── OptimisticLockException.php       # NEW
│   │   │   ├── SelfRoleChangeException.php       # NEW
│   │   │   └── LastAdminProtectionException.php  # NEW
│   │   └── Repositories/
│   │       └── StaffRepositoryInterface.php      # MODIFY (countAdmins)
│   ├── Infrastructure/
│   │   └── AuditLog/
│   │       └── StaffAuditLogger.php              # MODIFY (logStaffUpdated, logPasswordReset)
│   └── Presentation/HTTP/
│       ├── Controllers/
│       │   └── StaffAccountController.php        # MODIFY (show, update, resetPassword)
│       └── Requests/
│           └── UpdateStaffRequest.php            # NEW
└── routes/api.php                                # MODIFY

frontend/
├── src/
│   ├── features/staff-accounts/
│   │   ├── api/
│   │   │   └── staffAccountsApi.ts               # MODIFY (getStaff, updateStaff, resetPassword)
│   │   ├── components/
│   │   │   ├── StaffEditForm.tsx                 # NEW
│   │   │   └── PasswordResetDialog.tsx           # NEW
│   │   ├── hooks/
│   │   │   ├── useStaffDetail.ts                 # NEW
│   │   │   ├── useUpdateStaff.ts                 # NEW
│   │   │   └── useResetPassword.ts               # NEW
│   │   ├── schemas/
│   │   │   └── updateStaffSchema.ts              # NEW
│   │   └── types/
│   │       └── staffAccount.ts                   # MODIFY
│   └── pages/staff/
│       └── StaffAccountsEditPage.tsx             # NEW
└── src/app/router.tsx                            # MODIFY

tests/
├── backend/
│   ├── Unit/
│   │   └── Packages/Domain/Staff/Application/UseCases/
│   │       ├── Commands/UpdateStaff/
│   │       │   └── UpdateStaffHandlerTest.php    # NEW
│   │       ├── Commands/ResetPassword/
│   │       │   └── ResetPasswordHandlerTest.php  # NEW
│   │       └── Queries/GetStaffDetail/
│   │           └── GetStaffDetailHandlerTest.php # NEW
│   └── Feature/
│       └── Staff/
│           └── StaffAccountEditTest.php          # NEW
└── frontend/
    └── features/staff-accounts/
        ├── StaffEditForm.test.tsx                # NEW
        └── PasswordResetDialog.test.tsx          # NEW
```

**Structure Decision**: EPIC-003で確立したDDDアーキテクチャを継承。Backend/Frontendを分離し、各レイヤーの責務を明確化。新規ファイルはEPIC-003のパターンに従って配置。

## Complexity Tracking

該当なし（既存アーキテクチャの拡張のため）
