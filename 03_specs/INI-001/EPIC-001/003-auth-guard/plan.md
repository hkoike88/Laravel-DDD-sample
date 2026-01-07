# Implementation Plan: 認証ガードの実装

**Branch**: `005-auth-guard` | **Date**: 2025-12-26 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-auth-guard/spec.md`

## Summary

本フィーチャーは未ログインユーザーが職員向け画面にアクセスした場合にログイン画面へリダイレクトし、認証が必要な機能を保護する認証ガード機能の完成を目指す。既存の ProtectedRoute、GuestRoute、authStore、AuthProvider、useAuthCheck は実装済みであり、追加実装として useAuth フック、単体テスト、E2E テストの拡充を行う。

## Technical Context

**Language/Version**: TypeScript 5.3, React 18.2
**Primary Dependencies**: React Router 7.x, Zustand 5.x, TanStack Query 5.x, Axios 1.x
**Storage**: N/A（セッション管理はバックエンド Laravel Sanctum）
**Testing**: Vitest（単体テスト）, Playwright 1.57+（E2E テスト）, Testing Library
**Target Platform**: Web ブラウザ（モダンブラウザ対応）
**Project Type**: Web（frontend/backend 分離構成）
**Performance Goals**: リダイレクト完了 1 秒以内、認証確認 3 秒以内
**Constraints**: 既存実装との整合性を維持、Zustand ストアパターンに準拠
**Scale/Scope**: 保護対象 6 画面（ダッシュボード、蔵書管理、貸出処理、返却処理、利用者管理、予約管理）

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

プロジェクト Constitution が未定義（テンプレート状態）のため、以下の一般的なベストプラクティスに準拠：

| Gate | Status | Note |
|------|--------|------|
| テスト優先（TDD） | ✅ Pass | 単体テスト・E2E テストを実装 |
| シンプルさ（YAGNI） | ✅ Pass | 既存実装を活用、最小限の追加実装 |
| セキュリティ | ✅ Pass | 認証状態の一元管理、適切なリダイレクト |

## Project Structure

### Documentation (this feature)

```text
specs/005-auth-guard/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
frontend/
├── src/
│   ├── features/
│   │   └── auth/
│   │       ├── components/
│   │       │   ├── ProtectedRoute.tsx      # 実装済み
│   │       │   ├── GuestRoute.tsx          # 実装済み
│   │       │   └── AuthProvider.tsx        # 実装済み
│   │       ├── hooks/
│   │       │   ├── useAuthCheck.ts         # 実装済み
│   │       │   ├── useAuth.ts              # 新規追加
│   │       │   └── useLogin.ts             # 実装済み
│   │       ├── stores/
│   │       │   └── authStore.ts            # 実装済み
│   │       └── types/
│   │           └── auth.ts                 # 実装済み
│   └── app/
│       └── router.tsx                      # 実装済み
└── tests/
    └── e2e/
        ├── login.spec.ts                   # 部分実装済み（拡充対象）
        ├── dashboard.spec.ts               # 部分実装済み（拡充対象）
        └── auth-guard.spec.ts              # 新規追加
```

**Structure Decision**: 既存の frontend/ 構成を維持。新規追加ファイルは既存のディレクトリ構造に従う。

## Complexity Tracking

> 本フィーチャーは Constitution 違反なし。複雑性の正当化は不要。

| Item | Justification |
|------|--------------|
| N/A | 既存実装を活用した最小限の追加実装のため、追加の複雑性なし |
