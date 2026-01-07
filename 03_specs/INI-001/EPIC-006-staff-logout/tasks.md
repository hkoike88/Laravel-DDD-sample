# Tasks: 職員ログアウト機能

**Input**: Design documents from `/specs/001-staff-logout/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テスト追加がプランで指定されているため、テストタスクを含めます。

**Organization**: タスクはユーザーストーリーごとに整理され、独立した実装とテストが可能です。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: このタスクが属するユーザーストーリー（例: US1, US2, US3）
- 説明には正確なファイルパスを含む

## Path Conventions

- **Web app**: `backend/`, `frontend/src/`
- 本機能はフロントエンドのみの変更

---

## Phase 1: Setup

**Purpose**: 既存実装の確認と準備

- [x] T001 既存の useLogout フックの動作を確認 `frontend/src/features/auth/hooks/useLogout.ts`
- [x] T002 既存の LoginPage の構造を確認 `frontend/src/features/auth/pages/LoginPage.tsx`
- [x] T003 [P] 既存テストの実行確認 `frontend/src/features/auth/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: 本機能は既存実装への追加のため、基盤作業は不要

**⚠️ SKIP**: バックエンドAPI、認証ストア、ヘッダーのログアウトボタンは既に実装済み

**Checkpoint**: 既存実装が動作していることを確認後、ユーザーストーリーの実装を開始

---

## Phase 3: User Story 1 - 職員のログアウト操作 (Priority: P1) 🎯 MVP

**Goal**: ログアウト時に navigate state を渡してリダイレクト

**Independent Test**: ログアウトボタンをクリックし、ログイン画面にリダイレクトされ state が渡されることを確認

### Tests for User Story 1

> **NOTE: 既存テストを更新し、state 渡しを確認**

- [x] T004 [US1] useLogout フックのテストを更新（state 渡し確認） `frontend/src/features/auth/hooks/useLogout.test.tsx`

### Implementation for User Story 1

- [x] T005 [US1] useLogout フックを更新し navigate に state を追加 `frontend/src/features/auth/hooks/useLogout.ts`

**Checkpoint**: ログアウト時に `/login` へ `state: { loggedOut: true }` が渡されることを確認

---

## Phase 4: User Story 2 - ログアウト完了の通知 (Priority: P2)

**Goal**: ログイン画面でログアウト完了メッセージを表示、5秒後に自動非表示

**Independent Test**: ログアウト後にログイン画面で「ログアウトしました」メッセージが表示され、5秒後に消えることを確認

### Tests for User Story 2

> **NOTE: テストを先に作成し、失敗することを確認**

- [x] T006 [US2] LoginPage のログアウトメッセージ表示テストを作成 `frontend/src/features/auth/pages/LoginPage.test.tsx`
- [x] T007 [P] [US2] メッセージ自動非表示（5秒後）のテストを作成 `frontend/src/features/auth/pages/LoginPage.test.tsx`
- [x] T008 [P] [US2] 通常遷移時（state なし）にメッセージが表示されないテストを作成 `frontend/src/features/auth/pages/LoginPage.test.tsx`

### Implementation for User Story 2

- [x] T009 [US2] LoginPage に LocationState 型を定義 `frontend/src/features/auth/pages/LoginPage.tsx`
- [x] T010 [US2] LoginPage に useLocation で state を取得するロジックを追加 `frontend/src/features/auth/pages/LoginPage.tsx`
- [x] T011 [US2] LoginPage にログアウト完了メッセージ表示 UI を追加 `frontend/src/features/auth/pages/LoginPage.tsx`
- [x] T012 [US2] LoginPage に 5秒後の自動非表示ロジック（useEffect + setTimeout）を追加 `frontend/src/features/auth/pages/LoginPage.tsx`
- [x] T013 [US2] LoginPage にブラウザ履歴から state をクリアするロジックを追加 `frontend/src/features/auth/pages/LoginPage.tsx`

**Checkpoint**: ログアウト後にメッセージが表示され、5秒後に消え、リロードで再表示されないことを確認

---

## Phase 5: User Story 3 - 未認証状態でのログアウト試行 (Priority: P3)

**Goal**: セッション切れやログアウト済みの状態でログアウトを試みた場合のエラーハンドリング

**Independent Test**: 未認証状態でログアウトボタンをクリックしても、エラーなくログイン画面に遷移することを確認

**⚠️ NOTE**: 既存の useLogout フックで対応済み（エラー時もローカル状態をクリアして遷移）

### Verification for User Story 3

- [x] T014 [US3] 既存のエラーハンドリングが正しく動作することを確認 `frontend/src/features/auth/hooks/useLogout.ts`
- [x] T015 [P] [US3] セッション切れ時のログアウト動作をマニュアルテストで確認

**Checkpoint**: 未認証状態でもログイン画面に遷移し、エラーが表示されないことを確認

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: 全体的な品質向上

- [x] T016 全テストの実行と確認 `cd frontend && npm run test`
- [x] T017 [P] ESLint / Prettier による静的解析と修正 `cd frontend && npm run lint`
- [x] T018 quickstart.md に従った動作確認 `specs/001-staff-logout/quickstart.md`
- [ ] T019 コードレビュー依頼

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - 即時開始可能
- **Foundational (Phase 2)**: SKIP - 既存実装を活用
- **User Story 1 (Phase 3)**: Setup 完了後に開始
- **User Story 2 (Phase 4)**: User Story 1 完了後に開始（state の受け取りが前提）
- **User Story 3 (Phase 5)**: User Story 2 と並行可能（既存実装の確認のみ）
- **Polish (Phase 6)**: 全ユーザーストーリー完了後

### User Story Dependencies

```
Phase 1: Setup
    ↓
Phase 3: User Story 1 (ログアウト時に state を渡す)
    ↓
Phase 4: User Story 2 (LoginPage で state を受け取りメッセージ表示)
    ↓ (並行可能)
Phase 5: User Story 3 (エラーハンドリング確認)
    ↓
Phase 6: Polish
```

### Within Each User Story

- テスト → 実装 の順序で進行
- 同一ファイルへの変更は順次実行
- [P] マークがあるタスクは並列実行可能

### Parallel Opportunities

- T006, T007, T008: LoginPage のテストは並列実行可能（異なるテストケース）
- T014, T015: User Story 3 の確認タスクは並列実行可能

---

## Parallel Example: User Story 2

```bash
# Launch all tests for User Story 2 together (after T006 creates base test file):
Task: T007 "メッセージ自動非表示のテストを作成"
Task: T008 "通常遷移時にメッセージが表示されないテストを作成"
```

---

## Implementation Strategy

### MVP First (User Story 1 + User Story 2)

1. Complete Phase 1: Setup（既存実装確認）
2. Complete Phase 3: User Story 1（state 渡し）
3. Complete Phase 4: User Story 2（メッセージ表示）
4. **STOP and VALIDATE**: quickstart.md に従って動作確認
5. Deploy/demo if ready

### Incremental Delivery

1. User Story 1 完了 → ログアウト時に state が渡される
2. User Story 2 完了 → メッセージ表示が動作する
3. User Story 3 完了 → エラーハンドリングが確認済み
4. 各ストーリーは前のストーリーを壊さずに価値を追加

---

## Notes

- [P] tasks = 異なるファイル、依存関係なし
- [Story] ラベルはトレーサビリティのために特定のユーザーストーリーにマップ
- 各ユーザーストーリーは独立して完了・テスト可能
- テストが失敗することを確認してから実装
- タスクまたは論理グループごとにコミット
- 任意のチェックポイントで停止してストーリーを独立して検証可能
