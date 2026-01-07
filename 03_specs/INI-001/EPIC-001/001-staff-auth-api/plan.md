# Implementation Plan: 認証 API 実装

**Branch**: `002-staff-auth-api` | **Date**: 2025-12-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-staff-auth-api/spec.md`

## Summary

職員認証 API を Laravel Sanctum を使用して実装する。ログイン/ログアウト/認証状態確認の3つの主要エンドポイントを提供し、ST-001 で実装済みの Staff エンティティと連携する。セッションベースの SPA 認証モードを採用し、CSRF 保護とレート制限（5回/分）を適用する。

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Laravel Sanctum 4.x, symfony/uid（ULID生成）
**Storage**: MySQL 8.0（sessions テーブル、staffs テーブル）
**Testing**: Pest 3.x + Laravel Pest Plugin
**Target Platform**: Linux Docker コンテナ（Web API サーバー）
**Project Type**: Web application（backend API）
**Performance Goals**: API レスポンス < 200ms、認証処理 < 100ms
**Constraints**: セッション有効期限 2時間、レート制限 5回/分/IP
**Scale/Scope**: 業務アプリケーション、数百名の職員

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| DDD Architecture | ✅ Pass | Domain/Application/Infrastructure 層に準拠 |
| Test-First | ✅ Pass | Pest による Feature/Unit テスト |
| Security | ✅ Pass | CSRF保護、bcrypt、レート制限、セッション管理 |
| Code Style | ✅ Pass | Laravel Pint + Larastan |

**Gate Status**: PASSED - No violations

## Project Structure

### Documentation (this feature)

```text
specs/002-staff-auth-api/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output (OpenAPI)
│   └── auth-api.yaml
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── app/
│   └── Http/
│       └── Controllers/
│           └── Auth/
│               └── AuthController.php    # 認証コントローラー
├── packages/
│   └── Domain/
│       └── Staff/
│           ├── Domain/
│           │   ├── Model/Staff.php           # 既存エンティティ
│           │   ├── ValueObjects/             # 既存 VO
│           │   └── Repositories/             # 既存リポジトリI/F
│           ├── Application/
│           │   ├── UseCases/
│           │   │   └── Auth/
│           │   │       ├── LoginUseCase.php
│           │   │       ├── LogoutUseCase.php
│           │   │       └── GetCurrentUserUseCase.php
│           │   ├── DTO/
│           │   │   └── Auth/
│           │   │       ├── LoginRequest.php
│           │   │       └── StaffResponse.php
│           │   └── Repositories/
│           │       └── EloquentStaffRepository.php  # 既存
│           └── Infrastructure/
│               └── EloquentModels/
│                   └── StaffRecord.php       # 既存
├── routes/
│   └── api.php                               # 認証ルート追加
├── config/
│   ├── sanctum.php                           # Sanctum 設定
│   └── session.php                           # セッション設定
├── database/
│   └── migrations/
│       └── xxxx_create_sessions_table.php    # sessions テーブル
└── tests/
    ├── Feature/
    │   └── Auth/
    │       ├── LoginTest.php
    │       ├── LogoutTest.php
    │       └── GetCurrentUserTest.php
    └── Unit/
        └── Domain/
            └── Staff/
                └── Application/
                    └── UseCases/
                        └── Auth/
                            ├── LoginUseCaseTest.php
                            ├── LogoutUseCaseTest.php
                            └── GetCurrentUserUseCaseTest.php
```

**Structure Decision**: DDD レイヤードアーキテクチャに準拠。認証 UseCase は Application 層に配置し、HTTP 層のコントローラーは app/Http/Controllers/Auth に配置する。

## Complexity Tracking

> **No violations - table not required**
