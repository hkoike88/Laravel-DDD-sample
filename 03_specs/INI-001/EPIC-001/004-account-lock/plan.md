# Implementation Plan: アカウントロック機能

**Branch**: `006-account-lock` | **Date**: 2025-12-26 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/006-account-lock/spec.md`

## Summary

ブルートフォース攻撃からアカウントを保護するため、ログイン失敗が5回を超えたアカウントを自動でロックする機能を実装する。バックエンドのコア実装（Staff エンティティ、LoginUseCase、AccountLockedException）は既に完了しており、本フィーチャーではテストの追加とフロントエンドのエラー表示改善を行う。

## Technical Context

**Language/Version**: PHP 8.2+ (Backend), TypeScript 5.3 (Frontend)
**Primary Dependencies**: Laravel 12.x, Laravel Sanctum 4.x, React 18.x, TanStack Query 5.x
**Storage**: MySQL 8.0（staffs テーブルに is_locked, failed_login_attempts, locked_at カラム既存）
**Testing**: Pest (Backend), Vitest (Frontend Unit), Playwright (E2E)
**Target Platform**: Web application (SPA + API)
**Project Type**: Web application (frontend + backend)
**Performance Goals**: ログイン試行のレスポンス 1 秒以内
**Constraints**: ロック解除は Phase 2 で実装（本フィーチャーでは手動解除不可）
**Scale/Scope**: 職員数 100 名程度、同時ログイン試行 100 件

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution がテンプレート状態のため、プロジェクトの一般的なベストプラクティスに従う：

| Gate | Status | Notes |
|------|--------|-------|
| テスト必須 | ✅ PASS | Pest, Vitest, Playwright でテスト実装予定 |
| セキュリティ考慮 | ✅ PASS | 情報漏洩防止のエラーメッセージ設計済み |
| シンプルさ | ✅ PASS | 既存実装を活用、最小限の追加のみ |

## Project Structure

### Documentation (this feature)

```text
specs/006-account-lock/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
backend/
├── packages/Domain/Staff/
│   ├── Domain/Model/Staff.php              # ✅ 既存（lock 関連メソッド実装済み）
│   ├── Domain/Exceptions/
│   │   └── AccountLockedException.php      # ✅ 既存
│   └── Application/UseCases/Auth/
│       └── LoginUseCase.php                # ✅ 既存（5回失敗でロック実装済み）
├── database/migrations/
│   └── 2025_01_01_000000_create_staffs_table.php  # ✅ 既存（ロック関連カラム含む）
└── tests/
    ├── Unit/Domain/Staff/                  # 📝 追加予定
    │   ├── StaffAccountLockTest.php
    │   └── LoginUseCaseAccountLockTest.php
    └── Feature/                            # 📝 追加予定
        └── Auth/AccountLockFeatureTest.php

frontend/
├── src/features/auth/
│   ├── api/authApi.ts                      # ✅ 既存（423 エラー対応済み）
│   ├── types/auth.ts                       # ✅ 既存（locked タイプ定義済み）
│   ├── components/LoginForm.tsx            # 📝 更新予定（ロックメッセージ表示改善）
│   └── hooks/useLogin.ts                   # ✅ 既存
└── tests/e2e/
    └── account-lock.spec.ts                # 📝 追加予定

**Structure Decision**: 既存の DDD 構造を維持。テストファイルの追加とフロントエンドコンポーネントの軽微な更新のみ。
```

## Existing Implementation Analysis

### バックエンド（実装済み）

1. **Staff エンティティ** (`backend/packages/Domain/Staff/Domain/Model/Staff.php`)
   - `isLocked`, `failedLoginAttempts`, `lockedAt` プロパティ
   - `lock()`, `unlock()`, `incrementFailedLoginAttempts()`, `resetFailedLoginAttempts()` メソッド

2. **LoginUseCase** (`backend/packages/Domain/Staff/Application/UseCases/Auth/LoginUseCase.php`)
   - MAX_FAILED_ATTEMPTS = 5 定数
   - ロック状態チェック → AccountLockedException
   - パスワード失敗時 → 失敗回数インクリメント → 5回で自動ロック
   - 成功時 → 失敗回数リセット

3. **AccountLockedException** (`backend/packages/Domain/Staff/Domain/Exceptions/AccountLockedException.php`)
   - retryAfterSeconds プロパティ（30分 = 1800秒）

4. **データベース** (`backend/database/migrations/2025_01_01_000000_create_staffs_table.php`)
   - `is_locked` (boolean, default: false)
   - `failed_login_attempts` (unsigned int, default: 0)
   - `locked_at` (timestamp, nullable)

### フロントエンド（実装済み）

1. **authApi.ts** - 423 ステータスコードを `locked` タイプとして処理
2. **auth.ts types** - `ApiErrorType` に `'locked'` を含む

### 未実装・要追加

1. **バックエンドテスト**
   - Staff エンティティのロック関連メソッドの単体テスト
   - LoginUseCase のロック機能の単体テスト
   - Feature テスト（API 経由でのロック動作確認）

2. **フロントエンドテスト**
   - LoginForm のロックエラー表示テスト
   - E2E テスト（5回失敗 → ロック → 適切なメッセージ表示）

3. **フロントエンド改善**
   - LoginForm でのロックエラーメッセージの明確化

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| なし | - | 既存実装を活用、新規複雑性は追加しない |
