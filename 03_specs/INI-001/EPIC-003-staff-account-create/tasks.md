# Tasks: 職員アカウント作成機能

**Input**: Design documents from `/specs/007-staff-account-create/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: テスト作成は仕様書で明示的に要求されていないため、オプショナルとして含めています。

**Organization**: タスクはユーザーストーリーごとに整理され、独立した実装・テストが可能です。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: ユーザーストーリーの識別子（US1, US2）
- ファイルパスを含む具体的な説明

## Path Conventions

- **Backend**: `backend/`
- **Frontend**: `frontend/`
- 既存の DDD パッケージ構造に従う

---

## Phase 1: Setup（共通インフラ）

**Purpose**: フロントエンド機能の基盤となるディレクトリとファイルの作成

- [ ] T001 backend/packages/Domain/Staff/Application/DTO/StaffAccount/ ディレクトリを作成
- [ ] T002 [P] frontend/src/features/staff-accounts/ ディレクトリ構造を作成（api/, components/, hooks/, types/, schemas/）
- [ ] T003 [P] frontend/src/pages/staff/ ディレクトリを作成

---

## Phase 2: Foundational（基盤）

**Purpose**: すべてのユーザーストーリーの前提となるコア機能

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの実装は開始できません

- [ ] T004 管理者権限チェックミドルウェアを実装 backend/app/Http/Middleware/EnsureUserIsAdmin.php
- [ ] T005 [P] API ルートを追加（職員管理エンドポイント） backend/routes/api.php
- [ ] T006 [P] パスワード生成サービスを実装 backend/packages/Domain/Staff/Domain/Services/PasswordGenerator.php
- [ ] T007 [P] 監査ログサービスを実装 backend/packages/Domain/Staff/Infrastructure/AuditLog/StaffAuditLogger.php
- [ ] T008 [P] フロントエンド型定義を作成 frontend/src/features/staff-accounts/types/staffAccount.ts
- [ ] T009 [P] フロントエンド API クライアントを作成 frontend/src/features/staff-accounts/api/staffAccountsApi.ts

**Checkpoint**: 基盤が整い、ユーザーストーリーの実装を開始可能

---

## Phase 3: User Story 1 - 管理者による職員アカウント作成 (Priority: P1) 🎯 MVP

**Goal**: 管理者が新規職員を登録し、初期パスワードを取得できる

**Independent Test**: 管理者としてログインし、職員作成フォームに入力して送信すると、新しい職員アカウントが作成され、初期パスワードが表示される

### Backend 実装 for US1

- [ ] T010 [US1] CreateStaffInput DTO を作成 backend/packages/Domain/Staff/Application/DTO/StaffAccount/CreateStaffInput.php
- [ ] T011 [P] [US1] CreateStaffOutput DTO を作成 backend/packages/Domain/Staff/Application/DTO/StaffAccount/CreateStaffOutput.php
- [ ] T012 [US1] CreateStaffCommand を作成 backend/packages/Domain/Staff/Application/UseCases/Commands/CreateStaff/CreateStaffCommand.php
- [ ] T013 [US1] CreateStaffHandler を実装 backend/packages/Domain/Staff/Application/UseCases/Commands/CreateStaff/CreateStaffHandler.php
- [ ] T014 [US1] CreateStaffRequest（バリデーション）を作成 backend/packages/Domain/Staff/Presentation/HTTP/Requests/CreateStaffRequest.php
- [ ] T015 [US1] StaffAccountController の store メソッドを実装 backend/packages/Domain/Staff/Presentation/HTTP/Controllers/StaffAccountController.php

### Frontend 実装 for US1

- [ ] T016 [P] [US1] Zod バリデーションスキーマを作成 frontend/src/features/staff-accounts/schemas/createStaffSchema.ts
- [ ] T017 [P] [US1] useCreateStaff フックを実装 frontend/src/features/staff-accounts/hooks/useCreateStaff.ts
- [ ] T018 [P] [US1] PasswordDisplay コンポーネントを実装（マスク表示/表示/コピー機能） frontend/src/features/staff-accounts/components/PasswordDisplay.tsx
- [ ] T019 [US1] StaffCreateForm コンポーネントを実装 frontend/src/features/staff-accounts/components/StaffCreateForm.tsx
- [ ] T020 [US1] 職員作成画面を実装 frontend/src/pages/staff/StaffAccountsNewPage.tsx
- [ ] T021 [US1] 作成結果画面を実装 frontend/src/pages/staff/StaffAccountsResultPage.tsx
- [ ] T022 [US1] ルート定義を追加（/staff/accounts/new, /staff/accounts/result） frontend/src/routes/index.tsx

**Checkpoint**: User Story 1（職員アカウント作成）が独立して動作可能

---

## Phase 4: User Story 2 - 職員一覧の確認 (Priority: P2)

**Goal**: 管理者が登録済み職員をページネーション形式で確認できる

**Independent Test**: 管理者としてログインし、職員一覧画面にアクセスすると、登録済みの職員が20件ずつ表示される

### Backend 実装 for US2

- [ ] T023 [US2] StaffListOutput DTO を作成 backend/packages/Domain/Staff/Application/DTO/StaffAccount/StaffListOutput.php
- [ ] T024 [P] [US2] StaffListPaginatedOutput DTO を作成 backend/packages/Domain/Staff/Application/DTO/StaffAccount/StaffListPaginatedOutput.php
- [ ] T024a [P] [US2] PaginationLinks DTO を作成 backend/packages/Domain/Staff/Application/DTO/StaffAccount/PaginationLinks.php
- [ ] T025 [US2] StaffRepositoryInterface に findAllPaginated メソッドを追加 backend/packages/Domain/Staff/Domain/Repositories/StaffRepositoryInterface.php
- [ ] T026 [US2] EloquentStaffRepository に findAllPaginated を実装 backend/packages/Domain/Staff/Application/Repositories/EloquentStaffRepository.php
- [ ] T027 [US2] GetStaffListQuery を作成 backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffList/GetStaffListQuery.php
- [ ] T028 [US2] GetStaffListHandler を実装 backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffList/GetStaffListHandler.php
- [ ] T029 [US2] StaffAccountController の index メソッドを実装 backend/packages/Domain/Staff/Presentation/HTTP/Controllers/StaffAccountController.php

### Frontend 実装 for US2

- [ ] T030 [P] [US2] useStaffList フックを実装 frontend/src/features/staff-accounts/hooks/useStaffList.ts
- [ ] T031 [P] [US2] Pagination コンポーネントを実装 frontend/src/features/staff-accounts/components/Pagination.tsx
- [ ] T032 [US2] StaffListTable コンポーネントを実装 frontend/src/features/staff-accounts/components/StaffListTable.tsx
- [ ] T033 [US2] 職員一覧画面を実装 frontend/src/pages/staff/StaffAccountsListPage.tsx
- [ ] T034 [US2] ルート定義を追加（/staff/accounts） frontend/src/routes/index.tsx

**Checkpoint**: User Story 2（職員一覧）が独立して動作可能、US1との連携も機能

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: 全ユーザーストーリーに影響する改善

- [ ] T035 [P] StaffServiceProvider に新しい UseCase をバインド登録 backend/packages/Domain/Staff/Application/Providers/StaffServiceProvider.php
- [ ] T036 [P] 管理者用ナビゲーションに職員管理リンクを追加（既存のダッシュボードコンポーネント）
- [ ] T037 quickstart.md の手順に従って E2E 動作確認を実施
- [ ] T038 API レスポンス形式が openapi.yaml と一致することを確認

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - すべてのユーザーストーリーをブロック
- **User Stories (Phase 3, 4)**: Foundational 完了後
  - US1 と US2 は並列実行可能（スタッフがいれば）
  - または優先順（P1 → P2）で順次実行
- **Polish (Phase 5)**: 希望するユーザーストーリー完了後

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能 - 他のストーリーに依存しない
- **User Story 2 (P2)**: Foundational 完了後に開始可能 - US1 と統合するが独立してテスト可能

### Within Each User Story

- DTO → UseCase → Handler → Controller/Request の順
- Backend → Frontend の順（API 完成後に UI 実装）
- コアロジック → 統合の順

### Parallel Opportunities

- Phase 1: すべてのタスクが [P] で並列実行可能
- Phase 2: T005, T006, T007, T008, T009 が並列実行可能
- Phase 3 (US1): T016, T017, T018 が並列実行可能
- Phase 4 (US2): T024, T030, T031 が並列実行可能
- US1 と US2 は異なる開発者が並列で作業可能

---

## Parallel Example: Phase 3 (User Story 1)

```bash
# フロントエンドの並列タスク（バックエンド完了後）:
Task: "Zod バリデーションスキーマを作成 frontend/src/features/staff-accounts/schemas/createStaffSchema.ts"
Task: "useCreateStaff フックを実装 frontend/src/features/staff-accounts/hooks/useCreateStaff.ts"
Task: "PasswordDisplay コンポーネントを実装 frontend/src/features/staff-accounts/components/PasswordDisplay.tsx"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup 完了
2. Phase 2: Foundational 完了（重要 - すべてのストーリーをブロック）
3. Phase 3: User Story 1 完了
4. **STOP and VALIDATE**: User Story 1 を独立してテスト
5. 準備ができればデプロイ/デモ

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → 独立テスト → デプロイ/デモ（MVP!）
3. User Story 2 追加 → 独立テスト → デプロイ/デモ
4. 各ストーリーが前のストーリーを壊さずに価値を追加

### Parallel Team Strategy

複数開発者がいる場合:

1. チームで Setup + Foundational を完了
2. Foundational 完了後:
   - 開発者 A: User Story 1（バックエンド）
   - 開発者 B: User Story 2（バックエンド）
   - 開発者 C: User Story 1（フロントエンド、バックエンド完了後）
3. ストーリーが独立して完成・統合

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルは追跡のためタスクを特定のユーザーストーリーにマップ
- 各ユーザーストーリーは独立して完成・テスト可能
- 各タスクまたは論理的なグループの後にコミット
- 任意のチェックポイントで停止してストーリーを独立検証
- 避けるべきこと: 曖昧なタスク、同一ファイルの競合、独立性を壊すストーリー間依存

---

## Task Summary

| Phase | タスク数 | 並列可能 |
|-------|---------|---------|
| Phase 1: Setup | 3 | 2 |
| Phase 2: Foundational | 6 | 5 |
| Phase 3: User Story 1 | 13 | 4 |
| Phase 4: User Story 2 | 13 | 5 |
| Phase 5: Polish | 4 | 2 |
| **合計** | **39** | **18** |
