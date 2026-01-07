# Implementation Plan: 権限別メニュー表示

**Branch**: `003-role-based-menu` | **Date**: 2025-12-26 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-role-based-menu/spec.md`

## Summary

権限に基づいてダッシュボードのメニュー表示を制御する機能を実装する。管理者権限を持つ職員には「管理メニュー」セクション（職員管理）を表示し、一般職員には非表示にする。また、管理者専用ルートへの直接アクセスを権限チェックで保護し、一般職員が直接 URL にアクセスした場合は 403 エラーを返す。

バックエンドは既に `is_admin` フラグを API レスポンスに含めているため、フロントエンドの型定義を更新し、権限に基づく UI 制御とルートガードを実装する。

## Technical Context

**Language/Version**: PHP 8.3 (Backend), TypeScript 5.3 (Frontend)
**Primary Dependencies**: Laravel 11.x, Laravel Sanctum, React 18.x, React Router 7.x, Zustand 5.x, Tailwind CSS 3.x
**Storage**: MySQL 8.0（既存の staffs テーブル、sessions テーブル）
**Testing**: Pest (Backend), Vitest (Frontend)
**Target Platform**: Web Application (SPA)
**Project Type**: Web application (frontend + backend)
**Performance Goals**: 権限チェックは 1 秒以内に完了
**Constraints**: ちらつきなし（認証状態確認後にメニュー表示）、セキュリティ要件（403 エラーの適切な表示）
**Scale/Scope**: 単一の Web アプリケーション、管理者/一般職員の 2 種類の権限

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution ファイルがテンプレート状態のため、プロジェクト固有のゲートは定義されていません。CLAUDE.md に記載された標準に従います：

- [x] すべての返答・コメントは日本語
- [x] ドキュメンテーションコメントを記載
- [x] 品質を最優先し丁寧に実施
- [x] 既存のアーキテクチャパターン（DDD）に従う

## Project Structure

### Documentation (this feature)

```text
specs/003-role-based-menu/
├── plan.md              # このファイル
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output (API コントラクト)
└── tasks.md             # Phase 2 output (/speckit.tasks コマンドで作成)
```

### Source Code (repository root)

```text
backend/
├── app/
│   └── Http/
│       └── Middleware/
│           └── RequireAdmin.php           # 新規: 管理者権限チェックミドルウェア
├── routes/
│   └── api.php                             # 変更: 管理者専用ルートの追加
└── tests/
    └── Feature/
        └── Authorization/
            └── AdminAccessTest.php        # 新規: 管理者専用ルートのアクセステスト

frontend/
├── src/
│   ├── features/
│   │   ├── auth/
│   │   │   ├── types/
│   │   │   │   └── auth.ts                # 変更: Staff 型に is_admin 追加
│   │   │   └── stores/
│   │   │       └── authStore.ts           # 変更: isAdmin() ヘルパー追加（オプション）
│   │   └── dashboard/
│   │       ├── components/
│   │       │   └── AdminMenuSection.tsx   # 新規: 管理メニューセクション
│   │       ├── constants/
│   │       │   ├── menuItems.tsx          # 既存: 一般職員向けメニュー
│   │       │   └── adminMenuItems.tsx     # 新規: 管理者向けメニュー
│   │       └── pages/
│   │           └── DashboardPage.tsx      # 変更: AdminMenuSection 追加
│   ├── components/
│   │   └── guards/
│   │       └── AdminGuard.tsx             # 新規: 管理者専用ルートガード
│   └── router/
│       └── index.tsx                       # 変更: AdminGuard 適用
└── tests/
    └── features/
        └── dashboard/
            └── AdminMenuSection.test.tsx  # 新規: 管理メニューのテスト
```

**Structure Decision**: Web application 構成。既存の frontend/src/features 構造を踏襲し、dashboard 機能に AdminMenuSection を追加。認可チェックは既存の認証機能を拡張する形で実装。

## Complexity Tracking

> Constitution Check に違反がないため、この表は空です。
