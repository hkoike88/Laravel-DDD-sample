# Tasks: 認証ガードの実装

**Input**: Design documents from `/specs/005-auth-guard/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/, quickstart.md

**Tests**: 単体テスト（Vitest）および E2E テスト（Playwright）が必須として仕様書に明記されている。

**Organization**: タスクはユーザーストーリー単位で整理され、各ストーリーを独立して実装・テスト可能。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: 対象のユーザーストーリー（US1, US2, US3...）
- 説明には正確なファイルパスを含む

## Path Conventions

- **Web app**: `frontend/src/`（フロントエンド）
- テスト: `frontend/src/features/auth/` 配下（単体）、`frontend/tests/e2e/`（E2E）

---

## Phase 1: Setup (環境確認)

**Purpose**: 開発環境の確認と既存実装の検証

- [x] T001 既存の認証コンポーネント構成を確認（ProtectedRoute, GuestRoute, AuthProvider が正しく配置されているか）
- [x] T002 router.tsx で全保護ページが ProtectedRoute でラップされていることを確認 in frontend/src/app/router.tsx
- [x] T003 [P] 依存関係のバージョン確認（Vitest, Playwright, Testing Library が最新か）in frontend/package.json

---

## Phase 2: Foundational (基盤実装)

**Purpose**: 全ユーザーストーリーで共通して必要な useAuth フックの実装

**⚠️ CRITICAL**: 全てのユーザーストーリーのテストがこのフックに依存

- [x] T004 useAuth フックを実装 in frontend/src/features/auth/hooks/useAuth.ts
- [x] T005 useAuth フックを auth feature の index からエクスポート（必要に応じて）

**Checkpoint**: 基盤完了 - ユーザーストーリーの実装を開始可能

---

## Phase 3: User Story 1 - 未認証ユーザーの保護ページアクセス制御 (Priority: P1) 🎯 MVP

**Goal**: 未ログインユーザーが職員向け画面にアクセスした場合、ログイン画面にリダイレクト

**Independent Test**: 未認証状態でダッシュボード URL に直接アクセスし、ログイン画面にリダイレクトされることを確認

### Tests for User Story 1

- [x] T006 [P] [US1] ProtectedRoute の単体テストを実装 in frontend/src/features/auth/components/ProtectedRoute.test.tsx
- [x] T007 [P] [US1] E2E テスト：未認証時の全保護ページリダイレクト検証を実装 in frontend/tests/e2e/auth-guard.spec.ts

### Implementation for User Story 1

- [x] T008 [US1] ProtectedRoute の動作確認と必要に応じた改修 in frontend/src/features/auth/components/ProtectedRoute.tsx
- [x] T009 [US1] 単体テストを実行して PASS することを確認（npm run test:run）

**Checkpoint**: User Story 1 が完全に機能し、独立してテスト可能

---

## Phase 4: User Story 2 - 認証済みユーザーのログイン画面アクセス制御 (Priority: P1)

**Goal**: 認証済みユーザーがログイン画面にアクセスした場合、ダッシュボードにリダイレクト

**Independent Test**: ログイン済み状態でログイン URL に直接アクセスし、ダッシュボードにリダイレクトされることを確認

### Tests for User Story 2

- [x] T010 [P] [US2] GuestRoute の単体テストを実装 in frontend/src/features/auth/components/GuestRoute.test.tsx
- [x] T011 [P] [US2] E2E テスト：認証済み時のログイン画面リダイレクト検証を追加 in frontend/tests/e2e/auth-guard.spec.ts

### Implementation for User Story 2

- [x] T012 [US2] GuestRoute の動作確認と必要に応じた改修 in frontend/src/features/auth/components/GuestRoute.tsx
- [x] T013 [US2] 単体テストを実行して PASS することを確認

**Checkpoint**: User Stories 1 AND 2 がどちらも独立して動作

---

## Phase 5: User Story 3 - 認証状態のグローバル管理 (Priority: P1)

**Goal**: 認証状態がアプリケーション全体で一元管理され、どのページからでも参照可能

**Independent Test**: 複数のページで認証状態が一貫していることを確認。ログアウト後、全保護ページでリダイレクトされることを検証

### Tests for User Story 3

- [x] T014 [P] [US3] useAuth フックの単体テストを実装 in frontend/src/features/auth/hooks/useAuth.test.tsx
- [x] T015 [P] [US3] authStore の単体テストを実装 in frontend/src/features/auth/stores/authStore.test.ts
- [x] T016 [P] [US3] E2E テスト：認証状態の一貫性検証を追加 in frontend/tests/e2e/auth-guard.spec.ts

### Implementation for User Story 3

- [x] T017 [US3] authStore の動作確認 in frontend/src/features/auth/stores/authStore.ts
- [x] T018 [US3] AuthProvider の動作確認 in frontend/src/features/auth/components/AuthProvider.tsx
- [x] T019 [US3] 単体テストを実行して PASS することを確認

**Checkpoint**: 全 P1 ストーリーが完了し、独立して機能

---

## Phase 6: User Story 4 - ページ遷移時の認証チェック (Priority: P2)

**Goal**: ページ遷移のたびに認証状態がチェックされ、セキュリティが維持される

**Independent Test**: ページ遷移時に認証チェックが行われることを確認

### Tests for User Story 4

- [x] T020 [P] [US4] E2E テスト：ページ遷移時の認証チェック検証を追加 in frontend/tests/e2e/auth-guard.spec.ts

### Implementation for User Story 4

- [x] T021 [US4] ローディング表示が適切に動作することを確認 in frontend/src/features/auth/components/ProtectedRoute.tsx
- [x] T022 [US4] 認証確認フック useAuthCheck の動作確認 in frontend/src/features/auth/hooks/useAuthCheck.ts

**Checkpoint**: P2 ストーリー（US4）が完了

---

## Phase 7: User Story 5 - セッション期限切れ時の自動リダイレクト (Priority: P2)

**Goal**: セッションが期限切れになった場合、次の操作時に自動でログイン画面にリダイレクト

**Independent Test**: セッション期限切れ後の API 呼び出しで 401 エラーが返却された場合、ログイン画面にリダイレクトされることを確認

### Tests for User Story 5

- [x] T023 [P] [US5] E2E テスト：セッション期限切れ時のリダイレクト検証を追加 in frontend/tests/e2e/auth-guard.spec.ts

### Implementation for User Story 5

- [x] T024 [US5] 401 エラー時のリダイレクト動作を確認（既存実装で対応済みか検証）

**Checkpoint**: 全 P2 ストーリーが完了

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: 成功基準の検証と最終調整

- [x] T025 [P] テストカバレッジを計測し 80% 以上であることを確認（npm run test:coverage）→ 99.06%
- [x] T026 [P] E2E テスト全体を実行し全件 PASS することを確認（npm run test:e2e）→ 15 件 PASS
- [x] T027 パフォーマンス検証：リダイレクト完了が 1 秒以内であることを E2E テストで確認
- [x] T028 パフォーマンス検証：認証確認が 3 秒以内であることを E2E テストで確認
- [x] T029 quickstart.md の手順を実行して動作確認
- [x] T030 コードレビュー用のセルフチェック（ドキュメントコメント、型定義の整合性）

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即時開始可能
- **Foundational (Phase 2)**: Setup 完了に依存 - 全ユーザーストーリーをブロック
- **User Stories (Phase 3-7)**: Foundational 完了に依存
  - P1 ストーリー（US1, US2, US3）は並列実行可能
  - P2 ストーリー（US4, US5）は P1 完了後に開始推奨
- **Polish (Phase 8)**: 全ユーザーストーリー完了に依存

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能 - 他ストーリーに依存なし
- **User Story 2 (P1)**: Foundational 完了後に開始可能 - 他ストーリーに依存なし
- **User Story 3 (P1)**: Foundational 完了後に開始可能 - 他ストーリーに依存なし
- **User Story 4 (P2)**: P1 ストーリー完了後に開始推奨
- **User Story 5 (P2)**: P1 ストーリー完了後に開始推奨

### Within Each User Story

- テストを先に作成し、FAIL することを確認
- 実装してテストが PASS することを確認
- ストーリー完了後に次のストーリーへ移動

### Parallel Opportunities

- T003: 依存関係確認は独立して実行可能
- T006, T007: US1 のテストは並列実行可能
- T010, T011: US2 のテストは並列実行可能
- T014, T015, T016: US3 のテストは並列実行可能
- T025, T026: 最終検証は並列実行可能

---

## Parallel Example: User Story 1

```bash
# US1 のテストを並列で起動:
Task: "ProtectedRoute の単体テストを実装 in frontend/src/features/auth/components/ProtectedRoute.test.tsx"
Task: "E2E テスト：未認証時の全保護ページリダイレクト検証を実装 in frontend/tests/e2e/auth-guard.spec.ts"
```

---

## Parallel Example: P1 User Stories

```bash
# P1 ストーリーは Foundational 完了後に並列で作業可能:
Developer A: User Story 1 (未認証リダイレクト)
Developer B: User Story 2 (認証済みリダイレクト)
Developer C: User Story 3 (認証状態グローバル管理)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 完了: Setup
2. Phase 2 完了: Foundational（useAuth フック実装）
3. Phase 3 完了: User Story 1（未認証リダイレクト）
4. **STOP and VALIDATE**: US1 を独立してテスト
5. デプロイ/デモ可能

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → テスト → デプロイ (MVP!)
3. User Story 2 追加 → テスト → デプロイ
4. User Story 3 追加 → テスト → デプロイ
5. P2 ストーリー追加 → 最終テスト → 完了

### 単独作業時の推奨順序

1. T001-T005: Setup + Foundational
2. T004, T014, T015: useAuth + authStore のテスト・実装
3. T006, T008, T009: US1 完了
4. T010, T012, T013: US2 完了
5. T016-T019: US3 完了
6. T020-T024: P2 ストーリー完了
7. T025-T030: Polish

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルで特定のユーザーストーリーへの紐付けを明示
- 各ユーザーストーリーは独立して完了・テスト可能
- テストが FAIL してから実装
- 各タスクまたは論理グループ完了後にコミット
- 任意のチェックポイントで停止し、ストーリーを独立して検証可能
- 避けるべき: 曖昧なタスク、同一ファイル競合、ストーリー間の独立性を損なう依存関係
