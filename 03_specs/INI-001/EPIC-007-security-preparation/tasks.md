# Tasks: セキュリティ対策準備

**Input**: Design documents from `/specs/001-security-preparation/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/api.yaml

**Tests**: テストタスクは含みません（明示的な要求がないため）

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Backend**: `backend/` (Laravel DDD)
- **Frontend**: `frontend/` (React)
- **CI/CD**: `.github/workflows/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: 設定ファイルの作成と基盤構築

- [x] T001 Create hashing configuration in backend/config/hashing.php (bcrypt cost=12)
- [x] T002 [P] Add security logging channel to backend/config/logging.php
- [x] T003 [P] Create password_histories migration in backend/database/migrations/xxxx_create_password_histories_table.php
- [x] T004 Run migration and verify database schema

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: 全ユーザーストーリーが依存する共通コンポーネント

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T005 Create PasswordHistory domain model in backend/packages/Domain/Staff/Domain/Model/PasswordHistory.php
- [x] T006 [P] Create PasswordHistoryRepositoryInterface in backend/packages/Domain/Staff/Domain/Repositories/PasswordHistoryRepositoryInterface.php
- [x] T007 [P] Create EloquentPasswordHistory in backend/packages/Domain/Staff/Infrastructure/EloquentModels/EloquentPasswordHistory.php
- [x] T008 Implement PasswordHistoryRepository in backend/packages/Domain/Staff/Application/Repositories/PasswordHistoryRepository.php
- [x] T009 Register repository binding in service provider

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - パスワードポリシーの適用 (Priority: P1) 🎯 MVP

**Goal**: 職員がパスワードを設定・変更する際に、セキュリティ標準に準拠したパスワードポリシーが適用される

**Independent Test**: パスワード変更APIでポリシー違反パスワードが拒否され、適切なエラーメッセージが返却されることを確認

### Implementation for User Story 1

- [x] T010 [US1] Create PasswordPolicyRule custom validation rule in backend/app/Rules/PasswordPolicyRule.php
- [x] T011 [US1] Create PasswordNotCompromisedRule (HIBP check) in backend/app/Rules/PasswordNotCompromisedRule.php
- [x] T012 [US1] Create PasswordNotReusedRule in backend/app/Rules/PasswordNotReusedRule.php
- [x] T013 [US1] Implement PasswordHistoryService in backend/packages/Domain/Staff/Domain/Services/PasswordHistoryService.php
- [x] T014 [US1] Create ChangePasswordRequest FormRequest in backend/app/Http/Requests/Staff/ChangePasswordRequest.php
- [x] T015 [US1] Create ChangePasswordAction in backend/packages/Domain/Staff/Application/UseCases/ChangePassword/ChangePasswordAction.php
- [x] T016 [US1] Create ChangePasswordController in backend/app/Http/Controllers/Staff/ChangePasswordController.php
- [x] T017 [US1] Add PUT /api/v1/staff/password route in backend/routes/api.php
- [x] T018 [US1] Create Japanese error messages for password validation in backend/lang/ja/validation.php

**Checkpoint**: パスワードポリシー機能が独立して動作・テスト可能

---

## Phase 4: User Story 2 - セッションタイムアウトとセキュリティ (Priority: P1)

**Goal**: 職員のセッションが適切に管理され、一定時間後に自動ログアウトされる

**Independent Test**: 30分無操作後にセッションタイムアウトし、ログイン画面にリダイレクトされることを確認

### Implementation for User Story 2

- [x] T019 [US2] Create AbsoluteSessionTimeout middleware in backend/app/Http/Middleware/AbsoluteSessionTimeout.php
- [x] T020 [US2] Update session configuration to record session_start in payload
- [x] T021 [US2] Register AbsoluteSessionTimeout middleware in backend/bootstrap/app.php
- [x] T022 [US2] Implement session regeneration on login in existing LoginController
- [x] T023 [US2] Implement complete session destruction on logout in existing LogoutController
- [x] T024 [US2] Add session timeout error handling in frontend/src/lib/axios.ts

**Checkpoint**: セッションタイムアウト機能が独立して動作・テスト可能

---

## Phase 5: User Story 3 - 同時ログイン制御 (Priority: P2)

**Goal**: 職員の同時ログイン数が制限され、不正利用リスクが低減される

**Independent Test**: 一般職員で4台目ログイン時に最古セッションが無効化されることを確認

### Implementation for User Story 3

- [x] T025 [US3] Implement SessionManagerService in backend/packages/Domain/Staff/Domain/Services/SessionManagerService.php
- [x] T026 [US3] Create ConcurrentLoginMiddleware in backend/app/Http/Middleware/ConcurrentLoginMiddleware.php
- [x] T027 [US3] Integrate session limit enforcement into login process
- [x] T028 [US3] Create GetActiveSessionsAction in backend/packages/Domain/Staff/Application/UseCases/Session/GetActiveSessionsAction.php
- [x] T029 [US3] Create TerminateSessionAction in backend/packages/Domain/Staff/Application/UseCases/Session/TerminateSessionAction.php
- [x] T030 [US3] Create TerminateOtherSessionsAction in backend/packages/Domain/Staff/Application/UseCases/Session/TerminateOtherSessionsAction.php
- [x] T031 [US3] Create SessionController in backend/app/Http/Controllers/Staff/SessionController.php
- [x] T032 [US3] Add session management routes (GET /staff/sessions, DELETE /staff/sessions/{id}, DELETE /staff/sessions/others) in backend/routes/api.php
- [x] T033 [P] [US3] Create sessionApi service in frontend/src/features/auth/services/sessionApi.ts
- [x] T034 [P] [US3] Create useSessions hook in frontend/src/features/auth/hooks/useSessions.ts
- [x] T035 [US3] Create SessionList component in frontend/src/features/auth/components/SessionList.tsx
- [x] T036 [US3] Integrate SessionList into settings page

**Checkpoint**: 同時ログイン制御機能が独立して動作・テスト可能

---

## Phase 6: User Story 4 - 暗号化設定の適用 (Priority: P2)

**Goal**: システムがセキュリティ標準に準拠した暗号化設定を使用する

**Independent Test**: config/hashing.php で bcrypt cost=12、config/session.php で encrypt=true, secure=true, http_only=true, same_site=lax を確認

### Implementation for User Story 4

- [x] T037 [US4] Verify and document hashing configuration (bcrypt cost=12) in backend/config/hashing.php
- [x] T038 [US4] Verify session configuration (encrypt, secure, http_only, same_site) in backend/config/session.php
- [x] T039 [US4] Create security configuration verification script in backend/tests/Feature/SecurityConfigurationTest.php
- [x] T040 [US4] Document TLS 1.2+ configuration requirements in infrastructure/nginx/README.md

**Checkpoint**: 暗号化設定が確認・文書化完了

---

## Phase 7: User Story 5 - セキュリティスキャンの自動実行 (Priority: P2)

**Goal**: CI/CDパイプラインでセキュリティスキャンが自動実行される

**Independent Test**: GitHub Actions でセキュリティワークフローが実行され、レポートが生成されることを確認

### Implementation for User Story 5

- [x] T041 [US5] Create security.yml workflow in .github/workflows/security.yml
- [x] T042 [US5] Configure composer audit step with Critical/High failure threshold
- [x] T043 [US5] Configure npm audit step with Critical/High failure threshold
- [x] T044 [US5] Add PHPStan/Larastan security rules check
- [x] T045 [US5] Configure security scan report artifact upload

**Checkpoint**: セキュリティスキャンCI/CDが独立して動作・テスト可能

---

## Phase 8: User Story 6 - セキュリティログの記録 (Priority: P3)

**Goal**: セキュリティ関連イベントが適切にログに記録される

**Independent Test**: ログイン成功・失敗イベントを発生させ、storage/logs/security.log に記録されることを確認

### Implementation for User Story 6

- [x] T046 [US6] Create SecurityLogger service in backend/app/Services/SecurityLogger.php
- [x] T047 [US6] Implement login_success event logging
- [x] T048 [US6] Implement login_failure event logging
- [x] T049 [US6] Implement account_locked event logging (integrate with existing lock feature)
- [x] T050 [US6] Implement password_changed event logging in ChangePasswordAction
- [x] T051 [US6] Implement session_timeout event logging in AbsoluteSessionTimeout middleware
- [x] T052 [US6] Implement session_terminated event logging in SessionManagerService

**Checkpoint**: セキュリティログ記録が独立して動作・テスト可能

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: 横断的な改善と最終確認

- [x] T053 [P] Create password change form component in frontend/src/features/settings/components/PasswordChangeForm.tsx
- [x] T054 [P] Create usePasswordChange hook in frontend/src/features/settings/hooks/usePasswordChange.ts
- [x] T055 Integrate PasswordChangeForm into settings page
- [x] T056 Run quickstart.md validation (all steps executable)
- [x] T057 Final security configuration review and documentation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup (T001-T004) completion - BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational (Phase 2) completion
- **User Story 2 (Phase 4)**: Depends on Foundational (Phase 2) completion - Can run parallel to US1
- **User Story 3 (Phase 5)**: Depends on Foundational (Phase 2) completion - Can run parallel to US1/US2
- **User Story 4 (Phase 6)**: Depends on Setup (Phase 1) completion only
- **User Story 5 (Phase 7)**: No code dependencies - Can run parallel to other stories
- **User Story 6 (Phase 8)**: Depends on US1, US2 for integration points
- **Polish (Phase 9)**: Depends on US1 and US3 completion (for password change and session UI)

### User Story Dependencies

| User Story | Depends On | Can Parallelize With |
|------------|------------|---------------------|
| US1 (パスワードポリシー) | Foundational | US2, US4, US5 |
| US2 (セッションタイムアウト) | Foundational | US1, US3, US4, US5 |
| US3 (同時ログイン制御) | Foundational | US1, US2, US4, US5 |
| US4 (暗号化設定) | Setup only | US1, US2, US3, US5, US6 |
| US5 (セキュリティスキャン) | None | All |
| US6 (セキュリティログ) | US1, US2 | US4, US5 |

### Parallel Opportunities

**Phase 1 (Setup)**:
```
T001 (hashing.php) || T002 (logging.php) || T003 (migration)
```

**Phase 2 (Foundational)**:
```
T006 (RepositoryInterface) || T007 (EloquentModel)
```

**Phase 3-7 (User Stories 1-5)**:
```
[US1: T010-T018] || [US2: T019-T024] || [US3: T025-T036] || [US4: T037-T040] || [US5: T041-T045]
```

**Within US3**:
```
T033 (sessionApi.ts) || T034 (useSessions.ts)
```

---

## Parallel Example: User Story 3 (同時ログイン制御)

```bash
# Launch frontend tasks in parallel:
Task: "Create sessionApi service in frontend/src/features/auth/services/sessionApi.ts"
Task: "Create useSessions hook in frontend/src/features/auth/hooks/useSessions.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 + 2 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks stories)
3. Complete Phase 3: User Story 1 (パスワードポリシー)
4. Complete Phase 4: User Story 2 (セッションタイムアウト)
5. **STOP and VALIDATE**: Test US1 + US2 independently
6. Deploy/demo if ready - Core security is functional

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → **MVP: パスワードポリシー完了**
3. Add User Story 2 → Test independently → **セッション管理完了**
4. Add User Story 3 → Test independently → **同時ログイン制御完了**
5. Add User Story 4 → Verify settings → **暗号化設定確認完了**
6. Add User Story 5 → Test CI/CD → **セキュリティスキャン完了**
7. Add User Story 6 → Test logging → **セキュリティログ完了**
8. Polish → Final review → **全機能完了**

### Recommended Priority Order

| Priority | User Story | Rationale |
|----------|-----------|-----------|
| 1 | US1 + US2 | P1優先度、セキュリティの根幹 |
| 2 | US4 | 設定確認のみ、低コスト |
| 3 | US5 | CI/CD改善、他に影響なし |
| 4 | US3 | P2優先度、UI作業あり |
| 5 | US6 | P3優先度、統合ポイント多数 |

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- FR (Functional Requirement) からタスクへのマッピング:
  - FR-001〜FR-005 → US1 (T010-T018)
  - FR-006〜FR-009 → US2 (T019-T024)
  - FR-010〜FR-014 → US3 (T025-T036)
  - FR-015〜FR-018 → US4 (T037-T040)
  - FR-019〜FR-022 → US5 (T041-T045)
  - FR-023〜FR-028 → US6 (T046-T052)
