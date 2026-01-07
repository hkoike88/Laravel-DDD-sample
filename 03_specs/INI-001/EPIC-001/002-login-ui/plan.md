# Implementation Plan: ログイン画面実装

**Branch**: `003-login-ui` | **Date**: 2025-12-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-login-ui/spec.md`

## Summary

職員がメールアドレスとパスワードでシステムにログインするためのフロントエンド画面を実装する。React + TypeScript + TanStack Query + React Hook Form + Zod を使用し、既存の認証 API（ST-002）と連携する。ログイン成功時はダッシュボードへ遷移、失敗時は適切なエラーメッセージを表示する。

## Technical Context

**Language/Version**: TypeScript 5.3, React 18.2
**Primary Dependencies**: React Hook Form 7.x, Zod 4.x, TanStack Query 5.x, Axios 1.x, React Router 7.x, Zustand 5.x
**Storage**: N/A（セッションはバックエンドで管理、クッキーベース）
**Testing**: Vitest（Unit）, Playwright（E2E）, Testing Library
**Target Platform**: Web ブラウザ（Chrome, Firefox, Safari, Edge の最新版）
**Project Type**: Web application (frontend)
**Performance Goals**: ログイン完了まで 3 秒以内、バリデーションエラー表示 1 秒以内
**Constraints**: Sanctum SPA 認証（CSRF トークン必須）、アクセシビリティ対応（WCAG 2.1 AA）
**Scale/Scope**: 単一ログイン画面、認証状態管理

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution ファイルが未設定のため、プロジェクト標準に従う:
- ✅ フィーチャーベースのディレクトリ構成（features/auth/）
- ✅ コンポーネント単位でのテスト作成
- ✅ TypeScript strict mode
- ✅ ESLint + Prettier でコード品質確保

## Project Structure

### Documentation (this feature)

```text
specs/003-login-ui/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── auth-types.ts    # フロントエンド用型定義
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
frontend/
├── src/
│   ├── app/
│   │   ├── App.tsx
│   │   └── router.tsx        # ルート定義（/login, /dashboard 追加）
│   ├── features/
│   │   └── auth/             # 認証フィーチャー（新規）
│   │       ├── components/
│   │       │   └── LoginForm.tsx
│   │       ├── pages/
│   │       │   └── LoginPage.tsx
│   │       ├── hooks/
│   │       │   └── useLogin.ts
│   │       ├── api/
│   │       │   └── authApi.ts
│   │       ├── schemas/
│   │       │   └── loginSchema.ts
│   │       ├── stores/
│   │       │   └── authStore.ts
│   │       └── types/
│   │           └── auth.ts
│   ├── lib/
│   │   └── axios.ts          # 既存（CSRF 対応追加）
│   └── components/
│       └── ui/               # 共通 UI コンポーネント
└── tests/
    ├── unit/                 # Vitest ユニットテスト
    └── e2e/                  # Playwright E2E テスト
        └── login.spec.ts
```

**Structure Decision**: 既存の features ディレクトリ構成に従い、`features/auth/` を新規追加。books フィーチャーと同様のパターンを適用。

## Complexity Tracking

> 該当なし - Constitution 違反なし
