# Tasks: 職員アカウント編集機能

**Input**: Design documents from `/specs/001-epic-004-staff-account-edit/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/openapi.yaml

**Tests**: テスト実装を含む（plan.mdでPestとVitestが指定されているため）

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `backend/` (PHP/Laravel), `frontend/` (TypeScript/React)

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: 例外クラス、リポジトリインターフェース拡張、DTO など共通基盤の作成

- [x] T001 [P] Create OptimisticLockException in backend/packages/Domain/Staff/Domain/Exceptions/OptimisticLockException.php
- [x] T002 [P] Create SelfRoleChangeException in backend/packages/Domain/Staff/Domain/Exceptions/SelfRoleChangeException.php
- [x] T003 [P] Create LastAdminProtectionException in backend/packages/Domain/Staff/Domain/Exceptions/LastAdminProtectionException.php
- [x] T004 Extend StaffRepositoryInterface with countAdmins() in backend/packages/Domain/Staff/Domain/Repositories/StaffRepositoryInterface.php
- [x] T005 Implement countAdmins() in EloquentStaffRepository in backend/packages/Domain/Staff/Infrastructure/Repositories/EloquentStaffRepository.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: DTOの作成、監査ログ拡張、API Request クラスの作成

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T006 [P] Create UpdateStaffInput DTO in backend/packages/Domain/Staff/Application/DTO/StaffAccount/UpdateStaffInput.php
- [x] T007 [P] Create UpdateStaffOutput DTO in backend/packages/Domain/Staff/Application/DTO/StaffAccount/UpdateStaffOutput.php
- [x] T008 [P] Create ResetPasswordOutput DTO in backend/packages/Domain/Staff/Application/DTO/StaffAccount/ResetPasswordOutput.php
- [x] T009 [P] Create StaffDetailOutput DTO in backend/packages/Domain/Staff/Application/DTO/StaffAccount/StaffDetailOutput.php
- [x] T010 Extend StaffAuditLogger with logStaffUpdated() and logPasswordReset() in backend/packages/Domain/Staff/Infrastructure/AuditLog/StaffAuditLogger.php
- [x] T011 Create UpdateStaffRequest FormRequest in backend/packages/Domain/Staff/Presentation/HTTP/Requests/UpdateStaffRequest.php
- [x] T012 [P] Extend staffAccount.ts types in frontend/src/features/staff-accounts/types/staffAccount.ts
- [x] T013 [P] Create updateStaffSchema in frontend/src/features/staff-accounts/schemas/updateStaffSchema.ts

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - 職員基本情報の編集 (Priority: P1) 🎯 MVP

**Goal**: 管理者が職員の氏名、メールアドレス、権限を編集できる

**Independent Test**: 職員一覧から任意の職員を選択し、編集フォームで情報を変更して保存できることを確認

### Tests for User Story 1

- [ ] T014 [P] [US1] Unit test for GetStaffDetailHandler in backend/tests/Unit/Packages/Domain/Staff/Application/UseCases/Queries/GetStaffDetail/GetStaffDetailHandlerTest.php
- [ ] T015 [P] [US1] Unit test for UpdateStaffHandler in backend/tests/Unit/Packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/UpdateStaffHandlerTest.php
- [ ] T016 [P] [US1] Feature test for staff edit endpoints in backend/tests/Feature/Staff/StaffAccountEditTest.php
- [ ] T017 [P] [US1] Component test for StaffEditForm in frontend/tests/features/staff-accounts/StaffEditForm.test.tsx

### Implementation for User Story 1

- [ ] T018 [P] [US1] Create GetStaffDetailQuery in backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffDetail/GetStaffDetailQuery.php
- [ ] T019 [P] [US1] Create GetStaffDetailHandler in backend/packages/Domain/Staff/Application/UseCases/Queries/GetStaffDetail/GetStaffDetailHandler.php
- [ ] T020 [P] [US1] Create UpdateStaffCommand in backend/packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/UpdateStaffCommand.php
- [ ] T021 [US1] Create UpdateStaffHandler with validation and business rules in backend/packages/Domain/Staff/Application/UseCases/Commands/UpdateStaff/UpdateStaffHandler.php
- [ ] T022 [US1] Add show() and update() methods to StaffAccountController in backend/packages/Domain/Staff/Presentation/HTTP/Controllers/StaffAccountController.php
- [ ] T023 [US1] Add API routes for GET and PUT /staff/accounts/{id} in backend/routes/api.php
- [ ] T024 [P] [US1] Extend staffAccountsApi with getStaff() and updateStaff() in frontend/src/features/staff-accounts/api/staffAccountsApi.ts
- [ ] T025 [P] [US1] Create useStaffDetail hook in frontend/src/features/staff-accounts/hooks/useStaffDetail.ts
- [ ] T026 [P] [US1] Create useUpdateStaff hook in frontend/src/features/staff-accounts/hooks/useUpdateStaff.ts
- [ ] T027 [US1] Create StaffEditForm component in frontend/src/features/staff-accounts/components/StaffEditForm.tsx
- [ ] T028 [US1] Create StaffAccountsEditPage in frontend/src/pages/staff/StaffAccountsEditPage.tsx
- [ ] T029 [US1] Add edit route to router in frontend/src/app/router.tsx

**Checkpoint**: At this point, User Story 1 should be fully functional - 職員情報の編集が可能

---

## Phase 4: User Story 2 - パスワードリセット (Priority: P2)

**Goal**: 管理者が職員のパスワードをリセットし、新しい一時パスワードを発行できる

**Independent Test**: 編集画面のパスワードリセットボタンをクリックし、新しい一時パスワードが表示されることを確認

### Tests for User Story 2

- [ ] T030 [P] [US2] Unit test for ResetPasswordHandler in backend/tests/Unit/Packages/Domain/Staff/Application/UseCases/Commands/ResetPassword/ResetPasswordHandlerTest.php
- [ ] T031 [P] [US2] Component test for PasswordResetDialog in frontend/tests/features/staff-accounts/PasswordResetDialog.test.tsx

### Implementation for User Story 2

- [ ] T032 [P] [US2] Create ResetPasswordCommand in backend/packages/Domain/Staff/Application/UseCases/Commands/ResetPassword/ResetPasswordCommand.php
- [ ] T033 [US2] Create ResetPasswordHandler in backend/packages/Domain/Staff/Application/UseCases/Commands/ResetPassword/ResetPasswordHandler.php
- [ ] T034 [US2] Add resetPassword() method to StaffAccountController in backend/packages/Domain/Staff/Presentation/HTTP/Controllers/StaffAccountController.php
- [ ] T035 [US2] Add API route for POST /staff/accounts/{id}/reset-password in backend/routes/api.php
- [ ] T036 [P] [US2] Extend staffAccountsApi with resetPassword() in frontend/src/features/staff-accounts/api/staffAccountsApi.ts
- [ ] T037 [P] [US2] Create useResetPassword hook in frontend/src/features/staff-accounts/hooks/useResetPassword.ts
- [ ] T038 [US2] Create PasswordResetDialog component with clipboard copy in frontend/src/features/staff-accounts/components/PasswordResetDialog.tsx
- [ ] T039 [US2] Integrate PasswordResetDialog into StaffEditForm in frontend/src/features/staff-accounts/components/StaffEditForm.tsx

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently - パスワードリセットも可能

---

## Phase 5: User Story 3 - 自己権限変更の防止 (Priority: P2)

**Goal**: 管理者が自分自身の管理者権限を変更することを防止する

**Independent Test**: 管理者が自分自身の編集画面で権限変更を試みると、フィールドが無効化されていることを確認

### Implementation for User Story 3

- [ ] T040 [US3] Add self-role-change validation to UpdateStaffHandler (already partially in T021, enhance test coverage)
- [ ] T041 [US3] Add isCurrentUser check and disable role field in StaffEditForm in frontend/src/features/staff-accounts/components/StaffEditForm.tsx
- [ ] T042 [P] [US3] Add test case for self-role-change prevention in backend/tests/Feature/Staff/StaffAccountEditTest.php

**Checkpoint**: At this point, self-role-change prevention is enforced

---

## Phase 6: User Story 4 - 最後の管理者保護 (Priority: P2)

**Goal**: 最後の管理者アカウントの権限を一般職員に変更することを防止する

**Independent Test**: システムに管理者が1人だけの状態で、その管理者の権限変更を試みるとエラーになることを確認

### Implementation for User Story 4

- [ ] T043 [US4] Add last-admin protection validation to UpdateStaffHandler (already partially in T021, enhance test coverage)
- [ ] T044 [P] [US4] Add test case for last-admin protection in backend/tests/Feature/Staff/StaffAccountEditTest.php
- [ ] T045 [US4] Add frontend error handling for LAST_ADMIN_PROTECTION error code in frontend/src/features/staff-accounts/hooks/useUpdateStaff.ts

**Checkpoint**: At this point, last-admin protection is enforced

---

## Phase 7: User Story 5 - 同時編集の競合検出 (Priority: P3)

**Goal**: 複数の管理者が同時に同じ職員情報を編集した場合に競合を検出する

**Independent Test**: 2つのブラウザタブで同じ職員の編集画面を開き、一方で保存した後にもう一方で保存を試みると409エラーが表示されることを確認

### Implementation for User Story 5

- [ ] T046 [US5] Add optimistic lock validation to UpdateStaffHandler (already partially in T021, enhance error handling)
- [ ] T047 [P] [US5] Add test case for optimistic lock in backend/tests/Feature/Staff/StaffAccountEditTest.php
- [ ] T048 [US5] Add 409 conflict error handling and refresh button in StaffEditForm in frontend/src/features/staff-accounts/components/StaffEditForm.tsx

**Checkpoint**: At this point, optimistic locking conflict detection is functional

---

## Phase 8: User Story 6 - 監査ログ記録 (Priority: P3)

**Goal**: 職員アカウントの編集操作を監査ログに記録する

**Independent Test**: 職員情報を編集し、監査ログにレコードが記録されていることを確認

### Implementation for User Story 6

- [ ] T049 [US6] Integrate StaffAuditLogger.logStaffUpdated() call in UpdateStaffHandler (T010 must be complete)
- [ ] T050 [US6] Integrate StaffAuditLogger.logPasswordReset() call in ResetPasswordHandler (T010 must be complete)
- [ ] T051 [P] [US6] Add test cases for audit logging in backend/tests/Feature/Staff/StaffAccountEditTest.php

**Checkpoint**: At this point, all edit operations are logged

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: 最終調整、統合テスト、ドキュメント検証

- [ ] T052 Run all backend tests (pest) and fix any failures
- [ ] T053 Run all frontend tests (vitest) and fix any failures
- [ ] T054 Run PHPStan static analysis and fix issues
- [ ] T055 Run Pint code formatter
- [ ] T056 Run ESLint and Prettier for frontend
- [ ] T057 Run TypeScript type check
- [ ] T058 Manual testing per quickstart.md scenarios
- [ ] T059 Verify all API endpoints match openapi.yaml contract

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 completion - BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational (Phase 2) - MVP target
- **User Story 2 (Phase 4)**: Depends on Phase 2, can run parallel to US1 but integrates with edit form
- **User Story 3 (Phase 5)**: Depends on US1 (T021) - extends UpdateStaffHandler
- **User Story 4 (Phase 6)**: Depends on US1 (T021) - extends UpdateStaffHandler
- **User Story 5 (Phase 7)**: Depends on US1 (T021) - extends UpdateStaffHandler
- **User Story 6 (Phase 8)**: Depends on Phase 2 (T010 - AuditLogger), US1 and US2
- **Polish (Phase 9)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - Core editing functionality
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - Integrates with edit page
- **User Story 3 (P2)**: Depends on US1 (uses UpdateStaffHandler) - Business rule enhancement
- **User Story 4 (P2)**: Depends on US1 (uses UpdateStaffHandler) - Business rule enhancement
- **User Story 5 (P3)**: Depends on US1 (uses UpdateStaffHandler) - Error handling enhancement
- **User Story 6 (P3)**: Depends on Phase 2 T010 and US1/US2 handlers

### Within Each User Story

- Tests can be written in parallel with implementation
- Backend implementation before frontend integration
- Handlers before controllers
- API before hooks before components

### Parallel Opportunities

- Phase 1: T001, T002, T003 can run in parallel (exception classes)
- Phase 2: T006, T007, T008, T009 can run in parallel (DTOs), T012, T013 (frontend types/schemas)
- Phase 3: T014, T015, T016, T017 (tests), T018, T019, T020 (queries/commands)
- Phase 3: T024, T025, T026 (frontend API/hooks)
- Phase 4: T030, T031 (tests), T032, T036, T037 (backend command, frontend API/hooks)
- Phase 5-8: Test tasks marked [P] can run in parallel

---

## Parallel Example: Phase 1

```bash
# Launch all exception classes together:
Task: "Create OptimisticLockException"
Task: "Create SelfRoleChangeException"
Task: "Create LastAdminProtectionException"
```

## Parallel Example: User Story 1 Backend

```bash
# Launch tests together:
Task: "T014 Unit test for GetStaffDetailHandler"
Task: "T015 Unit test for UpdateStaffHandler"
Task: "T016 Feature test for staff edit endpoints"

# Launch query/command classes together:
Task: "T018 Create GetStaffDetailQuery"
Task: "T019 Create GetStaffDetailHandler"
Task: "T020 Create UpdateStaffCommand"
```

## Parallel Example: User Story 1 Frontend

```bash
# Launch API and hooks together:
Task: "T024 Extend staffAccountsApi"
Task: "T025 Create useStaffDetail hook"
Task: "T026 Create useUpdateStaff hook"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (例外、リポジトリ拡張)
2. Complete Phase 2: Foundational (DTO、監査ログ、リクエストクラス)
3. Complete Phase 3: User Story 1 (職員基本情報の編集)
4. **STOP and VALIDATE**: Test User Story 1 independently - 編集が動作することを確認
5. Deploy/demo if ready

### Incremental Delivery

1. Phase 1 + 2 → Foundation ready
2. Add User Story 1 → Test → Deploy/Demo (MVP!)
3. Add User Story 2 → Test → Deploy/Demo (パスワードリセット追加)
4. Add User Story 3-4 → Test (権限保護のビジネスルール)
5. Add User Story 5-6 → Test (競合検出、監査ログ)
6. Complete Phase 9 → Full feature release

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- US3, US4, US5, US6 are enhancements to the UpdateStaffHandler created in US1
- EPIC-003のパターンを継承: DDD構造、PasswordGenerator再利用、StaffAuditLogger拡張
