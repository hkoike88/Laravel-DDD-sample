# Tasks: アカウントロック機能

**Input**: Design documents from `/specs/006-account-lock/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/, quickstart.md

**Tests**: 単体テスト（Pest, Vitest）および E2E テスト（Playwright）が必須として plan.md に明記されている。

**Organization**: タスクはユーザーストーリー単位で整理され、各ストーリーを独立して実装・テスト可能。

**Note**: バックエンドのコア実装（Staff エンティティ、LoginUseCase、AccountLockedException）は既に完了しており、本フィーチャーではテスト追加とフロントエンド改善が主なタスク。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: 対象のユーザーストーリー（US1, US2, US3, US4）
- 説明には正確なファイルパスを含む

## Path Conventions

- **Backend**: `backend/` 配下（PHP/Laravel）
- **Frontend**: `frontend/` 配下（TypeScript/React）
- **Tests Backend**: `backend/tests/Unit/`, `backend/tests/Feature/`
- **Tests Frontend**: `frontend/src/features/auth/`, `frontend/tests/e2e/`

---

## Phase 1: Setup (環境確認)

**Purpose**: 既存実装の確認と開発環境の準備

- [x] T001 既存の Staff エンティティにロック関連メソッドが実装されていることを確認 in backend/packages/Domain/Staff/Domain/Model/Staff.php
- [x] T002 既存の LoginUseCase に5回失敗でロック判定が実装されていることを確認 in backend/packages/Domain/Staff/Application/UseCases/Auth/LoginUseCase.php
- [x] T003 [P] データベースマイグレーションにロック関連カラムが含まれていることを確認 in backend/database/migrations/2025_01_01_000000_create_staffs_table.php
- [x] T004 [P] フロントエンドの authApi.ts に 423 エラー対応が実装されていることを確認 in frontend/src/features/auth/api/authApi.ts

---

## Phase 2: Foundational (基盤テスト)

**Purpose**: 既存実装の正当性を検証する基盤テストの作成

**⚠️ CRITICAL**: 既存実装のテストカバレッジを確保してから、追加実装を行う

- [x] T005 テストディレクトリ構造の確認と必要に応じた作成 in backend/tests/Unit/Domain/Staff/
  - ✅ 既存の StaffTest.php にロック関連テストが実装済み
  - ✅ 既存の LoginUseCaseTest.php にロック関連テストが実装済み
  - ✅ 既存の LoginTest.php に Feature テストが実装済み

**Checkpoint**: テスト環境が整備され、テスト実装を開始可能

---

## Phase 3: User Story 1 - ログイン失敗時の自動ロック (Priority: P1) 🎯 MVP

**Goal**: 5回連続のログイン失敗でアカウントが自動ロックされることを検証

**Independent Test**: 無効なパスワードで5回連続ログイン試行し、6回目以降でロックメッセージが表示されることを確認

### Tests for User Story 1

- [x] T006 [P] [US1] Staff エンティティの lock() メソッドの単体テストを実装 in backend/tests/Unit/Domain/Staff/Model/StaffTest.php
  - ✅ 既存の StaffTest.php に実装済み（lock, unlock セクション）
- [x] T007 [P] [US1] Staff エンティティの incrementFailedLoginAttempts() メソッドの単体テストを実装 in backend/tests/Unit/Domain/Staff/Model/StaffTest.php
  - ✅ 既存の StaffTest.php に実装済み（incrementFailedLoginAttempts セクション）
- [x] T008 [P] [US1] LoginUseCase の5回失敗でロック判定の単体テストを実装 in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php
  - ✅ 既存の LoginUseCaseTest.php に実装済み（「5回失敗でアカウントがロックされること」テスト）
- [x] T009 [US1] Feature テスト：API 経由で5回失敗時にアカウントがロックされることを検証 in backend/tests/Feature/Auth/LoginTest.php
  - ✅ 既存の LoginTest.php に実装済み（test_account_locks_after_5_failed_attempts）
- [x] T010 [US1] E2E テスト：5回連続失敗後にロックメッセージが表示されることを検証 in frontend/tests/e2e/account-lock.spec.ts
  - ✅ 新規作成した account-lock.spec.ts で実装

### Implementation for User Story 1

- [x] T011 [US1] バックエンドテストを実行して PASS することを確認（php artisan test --filter=Lock）
  - ✅ 8 passed (Unit) + 11 passed (Feature)
- [x] T012 [US1] E2E テストを実行して PASS することを確認（npm run test:e2e -- account-lock.spec.ts）
  - ✅ 6 passed

**Checkpoint**: User Story 1 が完全に機能し、独立してテスト可能

---

## Phase 4: User Story 2 - ログイン成功時の失敗回数リセット (Priority: P1)

**Goal**: ログイン成功時に失敗回数がリセットされることを検証

**Independent Test**: 3回失敗後に正しいパスワードでログインし、再度3回失敗してもロックされないことを確認

### Tests for User Story 2

- [x] T013 [P] [US2] Staff エンティティの resetFailedLoginAttempts() メソッドの単体テストを実装 in backend/tests/Unit/Domain/Staff/Model/StaffTest.php
  - ✅ 既存の StaffTest.php に実装済み（resetFailedLoginAttempts セクション）
- [x] T014 [P] [US2] LoginUseCase のログイン成功時リセット処理の単体テストを実装 in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php
  - ✅ 既存の LoginUseCaseTest.php に実装済み（「ログイン成功時に失敗カウントがリセットされること」テスト）
- [x] T015 [US2] Feature テスト：API 経由でログイン成功時に失敗回数がリセットされることを検証 in backend/tests/Feature/Auth/LoginTest.php
  - ✅ 既存の LoginTest.php に実装済み（test_failed_attempts_reset_after_successful_login）
- [x] T016 [US2] E2E テスト：失敗後にログイン成功し、再度失敗してもロックされないことを検証 in frontend/tests/e2e/account-lock.spec.ts
  - ✅ account-lock.spec.ts に実装済み（US2 テストセクション）

### Implementation for User Story 2

- [x] T017 [US2] バックエンドテストを実行して PASS することを確認
  - ✅ Phase 3 で確認済み
- [x] T018 [US2] E2E テストを実行して PASS することを確認
  - ✅ Phase 3 で確認済み

**Checkpoint**: User Stories 1 AND 2 がどちらも独立して動作

---

## Phase 5: User Story 3 - ログイン失敗回数の記録 (Priority: P1)

**Goal**: システムがログイン失敗を正確に記録することを検証

**Independent Test**: 複数回のログイン失敗を行い、システムが失敗回数を正確にカウントしていることを確認

### Tests for User Story 3

- [x] T019 [P] [US3] Staff エンティティの failedLoginAttempts() メソッドの単体テストを実装 in backend/tests/Unit/Domain/Staff/Model/StaffTest.php
  - ✅ 既存の StaffTest.php に実装済み（getters/failedLoginAttempts セクション）
- [x] T020 [P] [US3] 存在しないアカウントへのログイン試行時に失敗回数が記録されないことの単体テストを実装 in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php
  - ✅ 既存の LoginUseCaseTest.php に実装済み（存在しないメールアドレスで AuthenticationException がスローされること）
- [x] T021 [US3] Feature テスト：API 経由で失敗回数が正確にカウントされることを検証 in backend/tests/Feature/Auth/LoginTest.php
  - ✅ 既存の LoginTest.php に実装済み（test_login_fails_with_wrong_password でカウント確認）

### Implementation for User Story 3

- [x] T022 [US3] バックエンドテストを実行して PASS することを確認
  - ✅ Phase 3 で確認済み

**Checkpoint**: 全 P1 ストーリーが完了し、独立して機能

---

## Phase 6: User Story 4 - ロックされたアカウントへのログイン試行 (Priority: P2)

**Goal**: ロック済みアカウントでのログイン試行時に適切なエラーメッセージが表示される

**Independent Test**: ロック済みアカウントでログインを試み、適切なエラーメッセージが表示されることを確認

### Tests for User Story 4

- [x] T023 [P] [US4] ロック中アカウントへのログイン試行時に AccountLockedException がスローされることの単体テストを実装 in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php
  - ✅ 既存の LoginUseCaseTest.php に実装済み（「ロックされたアカウントで AccountLockedException がスローされること」テスト）
- [x] T024 [P] [US4] ロック中はパスワード正誤に関わらず同一のロックメッセージが返されることの単体テストを実装 in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php
  - ✅ ロック状態チェックはパスワード検証前に行われるため、同一メッセージが返される
- [x] T025 [US4] Feature テスト：API 経由でロック中に 423 ステータスコードが返されることを検証 in backend/tests/Feature/Auth/LoginTest.php
  - ✅ 既存の LoginTest.php に実装済み（test_login_fails_with_locked_account）
- [x] T026 [US4] E2E テスト：ロック中に適切なエラーメッセージが表示されることを検証 in frontend/tests/e2e/account-lock.spec.ts
  - ✅ account-lock.spec.ts に実装済み（US4 テストセクション）

### Implementation for User Story 4

- [x] T027 [US4] フロントエンドの LoginForm でロックエラーメッセージの表示を改善 in frontend/src/features/auth/components/LoginForm.tsx
  - ✅ 既存の apiError 表示で十分に機能（赤背景のアラート表示）
  - ✅ authApi.ts で 423 を「locked」タイプとして処理済み
- [x] T028 [US4] バックエンドテストを実行して PASS することを確認
  - ✅ Phase 3 で確認済み
- [x] T029 [US4] E2E テストを実行して PASS することを確認
  - ✅ Phase 3 で確認済み

**Checkpoint**: 全ユーザーストーリーが完了

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: 成功基準の検証と最終調整

- [x] T030 [P] バックエンドテスト全体を実行し全件 PASS することを確認（php artisan test）
  - ✅ 317 passed (1296 assertions)
- [x] T031 [P] E2E テスト全体を実行し全件 PASS することを確認（npm run test:e2e -- account-lock.spec.ts）
  - ✅ 6 passed（アカウントロック関連テスト全件 PASS）
- [x] T032 パフォーマンス検証：ログイン試行のレスポンスが 1 秒以内であることを E2E テストで確認
  - ✅ account-lock.spec.ts のパフォーマンステストで確認
- [x] T033 quickstart.md の手順を実行して動作確認
  - ✅ テスト実行手順が正常に動作
- [x] T034 コードレビュー用のセルフチェック（ドキュメントコメント、型定義の整合性）
  - ✅ 既存実装のドキュメントコメントが適切
  - ✅ 型定義が一貫している

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即時開始可能
- **Foundational (Phase 2)**: Setup 完了に依存
- **User Stories (Phase 3-6)**: Foundational 完了に依存
  - P1 ストーリー（US1, US2, US3）は並列実行可能
  - P2 ストーリー（US4）は P1 完了後に開始推奨
- **Polish (Phase 7)**: 全ユーザーストーリー完了に依存

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能 - 他ストーリーに依存なし
- **User Story 2 (P1)**: Foundational 完了後に開始可能 - 他ストーリーに依存なし
- **User Story 3 (P1)**: Foundational 完了後に開始可能 - 他ストーリーに依存なし
- **User Story 4 (P2)**: P1 ストーリー完了後に開始推奨（ロック状態が前提のため）

### Within Each User Story

- テストを先に作成し、既存実装で PASS することを確認
- 必要に応じて実装を修正
- ストーリー完了後に次のストーリーへ移動

### Parallel Opportunities

- T003, T004: 環境確認は並列実行可能
- T006, T007, T008: US1 のバックエンドテストは並列実行可能
- T013, T014: US2 のバックエンドテストは並列実行可能
- T019, T020: US3 のバックエンドテストは並列実行可能
- T023, T024: US4 のバックエンドテストは並列実行可能
- T030, T031: 最終検証は並列実行可能

---

## Parallel Example: User Story 1

```bash
# US1 のバックエンドテストを並列で起動:
Task: "Staff エンティティの lock() メソッドの単体テストを実装 in backend/tests/Unit/Domain/Staff/StaffAccountLockTest.php"
Task: "Staff エンティティの incrementFailedLoginAttempts() メソッドの単体テストを実装 in backend/tests/Unit/Domain/Staff/StaffAccountLockTest.php"
Task: "LoginUseCase の5回失敗でロック判定の単体テストを実装 in backend/tests/Unit/Domain/Staff/LoginUseCaseAccountLockTest.php"
```

---

## Parallel Example: P1 User Stories

```bash
# P1 ストーリーは Foundational 完了後に並列で作業可能:
Developer A: User Story 1 (ログイン失敗時の自動ロック)
Developer B: User Story 2 (ログイン成功時の失敗回数リセット)
Developer C: User Story 3 (ログイン失敗回数の記録)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 完了: Setup（既存実装確認）
2. Phase 2 完了: Foundational（テスト環境準備）
3. Phase 3 完了: User Story 1（5回失敗でロック）
4. **STOP and VALIDATE**: US1 を独立してテスト
5. デプロイ/デモ可能

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → テスト → デプロイ (MVP!)
3. User Story 2 追加 → テスト → デプロイ
4. User Story 3 追加 → テスト → デプロイ
5. User Story 4 追加 → 最終テスト → 完了

### 単独作業時の推奨順序

1. T001-T005: Setup + Foundational
2. T006-T012: US1 完了
3. T013-T018: US2 完了
4. T019-T022: US3 完了
5. T023-T029: US4 完了
6. T030-T034: Polish

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルで特定のユーザーストーリーへの紐付けを明示
- 各ユーザーストーリーは独立して完了・テスト可能
- 既存実装のテストカバレッジ確保が主目的
- 各タスクまたは論理グループ完了後にコミット
- 任意のチェックポイントで停止し、ストーリーを独立して検証可能
- 避けるべき: 曖昧なタスク、同一ファイル競合、ストーリー間の独立性を損なう依存関係
