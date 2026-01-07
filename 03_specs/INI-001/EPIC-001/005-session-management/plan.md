# Implementation Plan: セッション管理実装

**Branch**: `001-session-management` | **Date**: 2025-12-26 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-session-management/spec.md`

## Summary

セッション管理機能を実装し、セキュリティを強化する。主な機能として、アイドルタイムアウト（30分）、絶対タイムアウト（8時間）、同時ログイン制限（職員3台/管理者1台）、セッション永続化（データベース）、CSRF保護を提供する。Laravel の標準セッション機能を基盤とし、カスタムミドルウェアでタイムアウトと同時ログイン制御を実装する。

## Technical Context

**Language/Version**: PHP 8.3, TypeScript 5.3
**Primary Dependencies**: Laravel 11.x, Laravel Sanctum 4.x, React 18.x, Zustand 5.x, Axios 1.x
**Storage**: MySQL 8.0（sessions テーブル、staffs テーブル）
**Testing**: Pest（バックエンド）, Vitest（フロントエンド）
**Target Platform**: Web アプリケーション（Docker コンテナ）
**Project Type**: Web（backend + frontend）
**Performance Goals**: セッション操作 < 100ms、同時ログイン制御 < 1秒
**Constraints**: HTTPS 必須、セッション暗号化必須、Cookie は HttpOnly/Secure/SameSite=Lax
**Scale/Scope**: 職員数 100〜1000名程度を想定

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Constitution ファイルがテンプレート状態のため、プロジェクトの CLAUDE.md に記載された以下のガイドラインを参照：

| Gate | Status | Notes |
|------|--------|-------|
| 日本語でのコミュニケーション | PASS | すべてのドキュメントとコメントは日本語 |
| ドキュメンテーションコメント必須 | PASS | 新規追加・修正時にはPHPDoc/JSDocを記載 |
| 品質優先 | PASS | 丁寧に作業を行い、品質を最優先 |
| 既存アーキテクチャ準拠 | PASS | DDD パターンに従う |

## Project Structure

### Documentation (this feature)

```text
specs/001-session-management/
├── spec.md              # 仕様書
├── plan.md              # 本ファイル（実装計画）
├── research.md          # Phase 0 出力（調査結果）
├── data-model.md        # Phase 1 出力（データモデル）
├── quickstart.md        # Phase 1 出力（クイックスタート）
├── contracts/           # Phase 1 出力（API契約）
│   └── session-api.yaml # セッション関連API定義
├── checklists/          # チェックリスト
│   └── requirements.md  # 要件チェックリスト
└── tasks.md             # Phase 2 出力（タスク一覧）
```

### Source Code (repository root)

```text
backend/
├── config/
│   └── session.php                      # セッション設定（更新）
├── app/
│   └── Http/
│       └── Middleware/
│           ├── AbsoluteSessionTimeout.php      # 絶対タイムアウトミドルウェア（新規）
│           └── ConcurrentSessionLimit.php      # 同時ログイン制限ミドルウェア（新規）
├── packages/
│   └── Domain/
│       └── Staff/
│           ├── Domain/
│           │   └── Model/
│           │       └── Staff.php               # 管理者判定メソッド追加（更新）
│           └── Infrastructure/
│               └── EloquentModels/
│                   └── StaffRecord.php         # is_admin カラム対応（更新）
├── database/
│   └── migrations/
│       └── xxxx_add_is_admin_to_staffs_table.php    # 管理者フラグ追加（新規）
└── tests/
    └── Feature/
        └── Auth/
            └── SessionTest.php                 # セッション機能テスト（新規）

frontend/
├── src/
│   ├── lib/
│   │   └── axios.ts                     # セッションタイムアウト処理（更新）
│   └── stores/
│       └── authStore.ts                 # セッション状態管理（更新）
└── tests/
    └── unit/
        └── sessionTimeout.test.ts       # セッションタイムアウトテスト（新規）
```

**Structure Decision**: 既存の backend/frontend 構成を維持し、セッション管理に必要なミドルウェアとテストを追加する。

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A | - | - |

現時点で Constitution 違反はない。
