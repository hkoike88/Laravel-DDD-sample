# Implementation Plan: ダッシュボード画面

**Branch**: `004-dashboard-ui` | **Date**: 2025-12-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-dashboard-ui/spec.md`

## Summary

職員がログイン後に表示されるダッシュボード画面を実装する。業務メニュー（蔵書管理、貸出処理、返却処理、利用者管理、予約管理）をカード形式で表示し、ヘッダーにはナビゲーションメニュー、ユーザー名、ログアウトボタンを配置する。既存の認証機能（003-login-ui）を拡張し、共通レイアウトコンポーネント（Header, Footer, MainLayout）を実装する。

## Technical Context

**Language/Version**: TypeScript 5.3, React 18.2
**Primary Dependencies**: React Router 7.x, Zustand 5.x, Tailwind CSS 3.x
**Storage**: N/A（認証状態は既存の authStore で管理）
**Testing**: Vitest（Unit）, Playwright（E2E）, Testing Library
**Target Platform**: Web ブラウザ（Chrome, Firefox, Safari, Edge の最新版）
**Project Type**: Web application (frontend)
**Performance Goals**: ダッシュボード表示 3 秒以内、画面遷移 1 秒以内、ログアウト完了 2 秒以内
**Constraints**: レスポンシブデザイン対応、アクセシビリティ対応（WCAG 2.1 AA）
**Scale/Scope**: ダッシュボード画面、共通レイアウトコンポーネント、ログアウト機能

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution ファイルが未設定のため、プロジェクト標準に従う:
- ✅ フィーチャーベースのディレクトリ構成（features/dashboard/）
- ✅ 共通コンポーネントは components/layout/ に配置
- ✅ コンポーネント単位でのテスト作成
- ✅ TypeScript strict mode
- ✅ ESLint + Prettier でコード品質確保

## Project Structure

### Documentation (this feature)

```text
specs/004-dashboard-ui/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── dashboard-types.ts
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
frontend/
├── src/
│   ├── app/
│   │   └── router.tsx           # ルート定義（プレースホルダーページ追加）
│   ├── features/
│   │   ├── auth/                # 既存認証フィーチャー
│   │   │   ├── api/
│   │   │   │   └── authApi.ts   # logout() 追加
│   │   │   ├── hooks/
│   │   │   │   └── useLogout.ts # 新規
│   │   │   └── stores/
│   │   │       └── authStore.ts # 既存
│   │   └── dashboard/           # ダッシュボードフィーチャー（新規）
│   │       ├── components/
│   │       │   ├── MenuCard.tsx
│   │       │   ├── MenuGrid.tsx
│   │       │   └── WelcomeMessage.tsx
│   │       ├── pages/
│   │       │   └── DashboardPage.tsx
│   │       └── types/
│   │           └── menu.ts
│   ├── components/
│   │   └── layout/              # 共通レイアウト（新規）
│   │       ├── Header.tsx
│   │       ├── Footer.tsx
│   │       └── MainLayout.tsx
│   └── pages/                   # プレースホルダーページ
│       ├── BooksPage.tsx
│       ├── LoansPage.tsx
│       ├── ReturnsPage.tsx
│       ├── UsersPage.tsx
│       ├── ReservationsPage.tsx
│       └── NotImplementedPage.tsx
└── tests/
    ├── unit/
    │   └── features/dashboard/
    └── e2e/
        └── dashboard.spec.ts
```

**Structure Decision**: 既存の features ディレクトリ構成に従い、`features/dashboard/` を新規追加。共通レイアウトコンポーネントは `components/layout/` に配置し、全画面で再利用可能にする。

## Complexity Tracking

> 該当なし - Constitution 違反なし
