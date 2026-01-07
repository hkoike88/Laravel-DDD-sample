# Tasks: 認証 API 実装

**Input**: Design documents from `/specs/002-staff-auth-api/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テストカバレッジ 90% 以上が成功基準（SC-006）のため、テストタスクを含む

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `backend/` at repository root
- DDD Architecture: `backend/packages/Domain/Staff/Application/` for UseCases/DTO

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and session management setup

- [x] T001 Create sessions table migration in backend/database/migrations/
- [x] T002 [P] Update .env with SESSION_DRIVER=database, SESSION_LIFETIME=120
- [x] T003 [P] Update config/sanctum.php with SANCTUM_STATEFUL_DOMAINS
- [x] T004 [P] Update config/session.php with database driver settings
- [x] T005 Update config/auth.php to use StaffRecord as provider in backend/config/auth.php
- [ ] T006 Run migrations to create sessions table

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T007 [P] Create LoginRequest DTO in backend/packages/Domain/Staff/Application/DTO/Auth/LoginRequest.php
- [x] T008 [P] Create StaffResponse DTO in backend/packages/Domain/Staff/Application/DTO/Auth/StaffResponse.php
- [x] T009 [P] Create AuthenticationException in backend/packages/Domain/Staff/Domain/Exceptions/AuthenticationException.php
- [x] T010 [P] Create AccountLockedException in backend/packages/Domain/Staff/Domain/Exceptions/AccountLockedException.php
- [x] T011 Update StaffRecord to implement Authenticatable in backend/packages/Domain/Staff/Infrastructure/EloquentModels/StaffRecord.php
- [x] T012 Create AuthController skeleton in backend/app/Http/Controllers/Auth/AuthController.php
- [x] T013 Add auth routes to backend/routes/api.php with throttle middleware

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - ログイン認証 (Priority: P1) 🎯 MVP

**Goal**: 職員がメールアドレスとパスワードでシステムにログインできる

**Independent Test**: POST /api/auth/login を呼び出し、認証セッションが確立され職員情報が返却される

### Tests for User Story 1

- [x] T014 [P] [US1] Create LoginUseCaseTest in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php
- [x] T015 [P] [US1] Create LoginTest feature test in backend/tests/Feature/Auth/LoginTest.php

### Implementation for User Story 1

- [x] T016 [US1] Implement LoginUseCase in backend/packages/Domain/Staff/Application/UseCases/Auth/LoginUseCase.php
- [x] T017 [US1] Implement login() method in AuthController in backend/app/Http/Controllers/Auth/AuthController.php
- [x] T018 [US1] Add login validation with FormRequest in backend/app/Http/Requests/Auth/LoginFormRequest.php
- [x] T019 [US1] Verify LoginTest passes (successful login, invalid credentials, locked account, rate limit)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 4 - CSRF トークン取得 (Priority: P1)

**Goal**: フロントエンドがCSRFトークンを取得してセキュアなリクエストを送信できる

**Independent Test**: GET /sanctum/csrf-cookie を呼び出し、XSRF-TOKEN クッキーが設定される

### Tests for User Story 4

- [x] T020 [P] [US4] Create CsrfCookieTest feature test in backend/tests/Feature/Auth/CsrfCookieTest.php

### Implementation for User Story 4

- [x] T021 [US4] Verify Sanctum CSRF cookie route is available (Laravel Sanctum 標準提供)
- [x] T022 [US4] Verify CsrfCookieTest passes (cookie set, CSRF validation works)

**Checkpoint**: CSRF protection functional - frontend can now make secure requests

---

## Phase 5: User Story 2 - 認証状態確認 (Priority: P2)

**Goal**: ログイン済みの職員が自身の情報を取得できる

**Independent Test**: ログイン後に GET /api/auth/user を呼び出し、現在の職員情報が返却される

### Tests for User Story 2

- [x] T023 [P] [US2] Create GetCurrentUserUseCaseTest in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/GetCurrentUserUseCaseTest.php
- [x] T024 [P] [US2] Create GetCurrentUserTest feature test in backend/tests/Feature/Auth/GetCurrentUserTest.php

### Implementation for User Story 2

- [x] T025 [US2] Implement GetCurrentUserUseCase in backend/packages/Domain/Staff/Application/UseCases/Auth/GetCurrentUserUseCase.php
- [x] T026 [US2] Implement user() method in AuthController in backend/app/Http/Controllers/Auth/AuthController.php
- [x] T027 [US2] Verify GetCurrentUserTest passes (authenticated, unauthenticated)

**Checkpoint**: At this point, User Stories 1, 2, AND 4 should all work independently

---

## Phase 6: User Story 3 - ログアウト (Priority: P3)

**Goal**: ログイン済みの職員がシステムからログアウトできる

**Independent Test**: ログイン後に POST /api/auth/logout を呼び出し、セッションが無効化される

### Tests for User Story 3

- [x] T028 [P] [US3] Create LogoutUseCaseTest in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LogoutUseCaseTest.php
- [x] T029 [P] [US3] Create LogoutTest feature test in backend/tests/Feature/Auth/LogoutTest.php

### Implementation for User Story 3

- [x] T030 [US3] Implement LogoutUseCase in backend/packages/Domain/Staff/Application/UseCases/Auth/LogoutUseCase.php
- [x] T031 [US3] Implement logout() method in AuthController in backend/app/Http/Controllers/Auth/AuthController.php
- [x] T032 [US3] Verify LogoutTest passes (successful logout, session invalidation)

**Checkpoint**: All user stories should now be independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T033 [P] Run all tests and verify 90%+ coverage
- [x] T034 [P] Run Larastan static analysis
- [x] T035 [P] Run Laravel Pint code formatting
- [x] T036 Validate quickstart.md manual test scenarios
- [x] T037 Update requirements.md checklist with completion status

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-6)**: All depend on Foundational phase completion
  - US1 and US4 are both P1 - can proceed in parallel
  - US2 and US3 can proceed in parallel after P1 stories
- **Polish (Phase 7)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1) - ログイン**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 4 (P1) - CSRF**: Can start after Foundational (Phase 2) - Independent (Sanctum 標準)
- **User Story 2 (P2) - 認証確認**: Can start after Foundational - Uses authenticated session from US1
- **User Story 3 (P3) - ログアウト**: Can start after Foundational - Uses authenticated session from US1

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- DTO before UseCases
- UseCases before Controllers
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T002, T003, T004)
- All Foundational tasks marked [P] can run in parallel (T007, T008, T009, T010)
- Once Foundational phase completes:
  - US1 and US4 can start in parallel (both P1)
  - US2 and US3 can start in parallel after US1 login is available
- All tests for a user story marked [P] can run in parallel
- All Polish tasks marked [P] can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Create LoginUseCaseTest in backend/tests/Unit/Domain/Staff/Application/UseCases/Auth/LoginUseCaseTest.php"
Task: "Create LoginTest feature test in backend/tests/Feature/Auth/LoginTest.php"

# Then implement sequentially:
Task: "Implement LoginUseCase in backend/packages/Domain/Staff/Application/UseCases/Auth/LoginUseCase.php"
Task: "Implement login() method in AuthController"
```

---

## Implementation Strategy

### MVP First (User Story 1 + 4 Only)

1. Complete Phase 1: Setup (sessions table, config)
2. Complete Phase 2: Foundational (DTO, exceptions, routes)
3. Complete Phase 3: User Story 1 - ログイン
4. Complete Phase 4: User Story 4 - CSRF
5. **STOP and VALIDATE**: Test login flow end-to-end
6. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 + 4 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo
4. Add User Story 3 → Test independently → Deploy/Demo
5. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (ログイン)
   - Developer B: User Story 4 (CSRF) - 短時間で完了
   - Developer A: User Story 2 (認証確認) after US1
   - Developer B: User Story 3 (ログアウト) after US4
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- StaffRecord must implement Authenticatable for Sanctum to work
- CSRF cookie route is provided by Laravel Sanctum automatically
