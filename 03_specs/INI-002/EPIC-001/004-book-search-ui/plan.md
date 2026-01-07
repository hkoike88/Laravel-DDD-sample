# Implementation Plan: 蔵書検索画面

**Branch**: `004-book-search-ui` | **Date**: 2025-12-24 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-book-search-ui/spec.md`

## Summary

図書館職員向けの蔵書検索画面を実装する。タイトル・著者名・ISBNでの検索、検索結果一覧表示（状態バッジ付き）、ページネーション機能を提供する。既存の蔵書検索API（GET /api/books）を利用したフロントエンド実装。

## Technical Context

**Language/Version**: TypeScript 5.x
**Primary Dependencies**: React 18.x, TanStack Query 5.x, React Hook Form 7.x, Zod 4.x, Axios 1.x, Tailwind CSS 3.x
**Storage**: N/A（バックエンドAPIを利用）
**Testing**: Vitest + React Testing Library（後続タスクで追加予定）
**Target Platform**: モダンブラウザ（Chrome, Firefox, Edge, Safari最新版）
**Project Type**: Web application（frontend）
**Performance Goals**: 検索結果表示まで3秒以内
**Constraints**: デスクトップ向けレイアウト優先、認証不要
**Scale/Scope**: 図書館職員向け内部システム

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Gate | Status | Notes |
|------|--------|-------|
| 既存パターン準拠 | ✅ Pass | 既存のfrontend構造（features/, components/, hooks/）を踏襲 |
| TDD準拠 | ⚠️ Deferred | テストは後続タスクで追加（Vitest未導入） |
| コンポーネント分割 | ✅ Pass | 適切な粒度で分割予定 |

## Project Structure

### Documentation (this feature)

```text
specs/004-book-search-ui/
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
│   │   └── books/                    # 蔵書機能モジュール
│   │       ├── api/
│   │       │   └── bookApi.ts        # API クライアント
│   │       ├── components/
│   │       │   ├── BookSearchForm.tsx      # 検索フォーム
│   │       │   ├── BookSearchResults.tsx   # 検索結果一覧
│   │       │   ├── BookStatusBadge.tsx     # 状態バッジ
│   │       │   └── Pagination.tsx          # ページネーション
│   │       ├── hooks/
│   │       │   └── useBookSearch.ts        # 検索カスタムフック
│   │       ├── types/
│   │       │   └── book.ts                 # 型定義
│   │       └── pages/
│   │           └── BookSearchPage.tsx      # ページコンポーネント
│   ├── components/
│   │   ├── layout/                   # 既存レイアウト
│   │   └── ui/                       # 共通UIコンポーネント
│   ├── lib/
│   │   └── axios.ts                  # Axiosインスタンス設定（新規）
│   └── app/
│       └── router.tsx                # ルーター設定（更新）
└── tests/                            # テスト（後続タスク）
```

**Structure Decision**: Feature-based構造を採用。`frontend/src/features/books/`配下に蔵書機能関連のコードを集約し、関心の分離と再利用性を確保する。

## Complexity Tracking

> **No violations requiring justification**

既存のプロジェクト構造に従った実装のため、特別な複雑性の追加は不要。
