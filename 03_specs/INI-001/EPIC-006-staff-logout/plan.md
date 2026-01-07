# Implementation Plan: 職員ログアウト機能

**Branch**: `001-staff-logout` | **Date**: 2026-01-06 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-staff-logout/spec.md`

---

## Summary

職員がシステムからログアウトし、セッションを安全に終了する機能を実装する。
バックエンドのログアウトAPI（セッション破棄、CSRF再生成）は既に実装済み。
フロントエンドではログアウト後のメッセージ表示機能を追加実装する。

---

## Technical Context

**Language/Version**: PHP 8.3 (Backend), TypeScript 5.3 (Frontend)
**Primary Dependencies**: Laravel 11.x, Laravel Sanctum 4.x, React 18.x, React Router 7.x, Zustand 5.x
**Storage**: MySQL 8.0（sessions テーブル、既存）
**Testing**: Pest (Backend), Vitest (Frontend)
**Target Platform**: Web Browser (SPA)
**Project Type**: Web application (backend + frontend)
**Performance Goals**: ログアウト処理1秒以内
**Constraints**: 既存の認証フローとの整合性維持
**Scale/Scope**: 既存機能への軽微な追加（2ファイル変更、テスト追加）

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

プロジェクト憲法が未設定のため、既存アーキテクチャ設計に従う：

| ゲート | 状態 | 備考 |
|--------|------|------|
| DDD アーキテクチャ準拠 | PASS | 既存の Domain/Application/Presentation 層構造を維持 |
| Feature-based 構造準拠 | PASS | `features/auth/` 配下の既存構造を維持 |
| テスト必須 | PASS | 変更箇所にテストを追加 |
| 既存パターン踏襲 | PASS | useLogout、LoginPage の既存実装パターンに従う |

---

## Project Structure

### Documentation (this feature)

```text
specs/001-staff-logout/
├── spec.md              # Feature specification
├── plan.md              # This file
├── research.md          # Phase 0: 既存実装調査結果
├── data-model.md        # Phase 1: データモデル（変更なし）
├── quickstart.md        # Phase 1: 実装ガイド
├── contracts/           # Phase 1: API仕様（参照用）
│   └── logout-api.yaml
└── tasks.md             # Phase 2: タスク一覧（/speckit.tasks で生成）
```

### Source Code (repository root)

```text
backend/
├── app/Http/Controllers/Auth/
│   └── AuthController.php         # [既存] logout メソッド実装済み
└── packages/Domain/Staff/
    └── Application/UseCases/Auth/
        └── LogoutUseCase.php      # [既存] ログアウトユースケース実装済み

frontend/
├── src/
│   ├── features/auth/
│   │   ├── hooks/
│   │   │   ├── useLogout.ts       # [要修正] navigate に state を追加
│   │   │   └── useLogout.test.ts  # [要追加] state 渡しのテスト
│   │   └── pages/
│   │       ├── LoginPage.tsx      # [要修正] ログアウトメッセージ表示
│   │       └── LoginPage.test.tsx # [要追加] メッセージ表示テスト
│   └── components/layout/
│       └── Header.tsx             # [既存] ログアウトボタン実装済み
└── tests/
```

**Structure Decision**: 既存の Web application 構造（backend + frontend）を維持。
新規ディレクトリの追加は不要。

---

## Implementation Scope

### 既存実装（変更不要）

| コンポーネント | ファイル | 状態 |
|---------------|---------|------|
| ログアウトAPI | `AuthController::logout` | 実装済み |
| ログアウトユースケース | `LogoutUseCase` | 実装済み |
| API クライアント | `authApi.ts` | 実装済み |
| 認証ストア | `authStore.ts` | 実装済み |
| ヘッダー（ログアウトボタン） | `Header.tsx` | 実装済み |

### 新規実装（本機能で追加）

| コンポーネント | ファイル | 変更内容 |
|---------------|---------|---------|
| ログアウトフック | `useLogout.ts` | navigate に `state: { loggedOut: true }` を追加 |
| ログインページ | `LoginPage.tsx` | ログアウト完了メッセージ表示、5秒後自動非表示 |
| テスト | `useLogout.test.ts` | state 渡しの確認テスト |
| テスト | `LoginPage.test.tsx` | メッセージ表示/非表示テスト |

---

## Technical Decisions

### 1. メッセージ渡し方法

**採用**: React Router の navigate state
```typescript
navigate('/login', { replace: true, state: { loggedOut: true } })
```

**理由**:
- URLにパラメータを露出しない
- ページリロードでメッセージが再表示されない
- 既存パターンとの整合性

### 2. メッセージ自動非表示

**採用**: `useEffect` + `setTimeout` (5秒)

**理由**:
- シンプルな実装
- 追加ライブラリ不要
- ユーザーが確認できる十分な時間

### 3. 履歴からのstate削除

**採用**: `window.history.replaceState({}, document.title)`

**理由**:
- ブラウザの戻るボタンで再表示されない
- セキュリティ上好ましい

---

## Risks & Mitigations

| リスク | 影響度 | 軽減策 |
|--------|--------|--------|
| useEffect 無限ループ | 中 | 依存配列を正しく設定、レビューで確認 |
| テスト漏れ | 低 | 既存テストパターンに従う |
| 既存機能への影響 | 低 | 変更は最小限、既存テストで回帰確認 |

---

## Complexity Tracking

> 本機能では Constitution 違反や複雑性の正当化は不要

N/A - 既存実装への軽微な追加のみ

---

## Artifacts Generated

| ファイル | 説明 |
|---------|------|
| `research.md` | 既存実装の調査結果、技術的決定事項 |
| `data-model.md` | データモデル（既存流用、変更なし） |
| `contracts/logout-api.yaml` | API仕様（参照用、既存実装を文書化） |
| `quickstart.md` | 実装ガイド、動作確認手順 |

---

## Next Steps

1. `/speckit.tasks` コマンドでタスク一覧を生成
2. タスクを順番に実装
3. テスト実行・動作確認
4. コードレビュー
5. マージ
