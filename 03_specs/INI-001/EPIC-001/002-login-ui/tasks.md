# Tasks: ログイン画面実装

**Input**: Design documents from `/specs/003-login-ui/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テストは既存インフラ（Vitest, Playwright）を活用して作成

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `frontend/src/` at repository root
- DDD フィーチャー構成: `frontend/src/features/auth/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: 認証フィーチャーのディレクトリ構成と共通設定

- [x] T001 Create auth feature directory structure in frontend/src/features/auth/
- [x] T002 [P] Create type definitions in frontend/src/features/auth/types/auth.ts
- [x] T003 [P] Update Axios config for CSRF support in frontend/lib/axios.ts

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T004 Create auth API client in frontend/src/features/auth/api/authApi.ts
- [x] T005 [P] Create auth store (Zustand) in frontend/src/features/auth/stores/authStore.ts
- [x] T006 [P] Create login form schema (Zod) in frontend/src/features/auth/schemas/loginSchema.ts
- [x] T007 Add /login and /dashboard routes to frontend/src/app/router.tsx
- [x] T008 Create placeholder Dashboard page in frontend/src/pages/DashboardPage.tsx

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - ログイン認証 (Priority: P1) 🎯 MVP

**Goal**: 職員がメールアドレスとパスワードでログインし、ダッシュボードへ遷移できる

**Independent Test**: ログイン画面で有効な認証情報を入力し、ログインボタンを押すことで、ダッシュボードへ遷移することを確認

### Tests for User Story 1

- [x] T009 [P] [US1] Create LoginForm unit test in frontend/src/features/auth/components/LoginForm.test.tsx
- [x] T010 [P] [US1] Create useLogin hook unit test in frontend/src/features/auth/hooks/useLogin.test.ts

### Implementation for User Story 1

- [x] T011 [US1] Implement useLogin hook in frontend/src/features/auth/hooks/useLogin.ts
- [x] T012 [US1] Create LoginForm component in frontend/src/features/auth/components/LoginForm.tsx
- [x] T013 [US1] Create LoginPage in frontend/src/features/auth/pages/LoginPage.tsx
- [x] T014 [US1] Add loading state and button disable during login submission
- [x] T015 [US1] Verify tests pass (login success, redirect to dashboard)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - 認証エラー表示 (Priority: P1)

**Goal**: 無効な認証情報でログイン失敗時に適切なエラーメッセージを表示

**Independent Test**: 無効な認証情報でログインを試み、適切なエラーメッセージが表示されることを確認

### Tests for User Story 2

- [x] T016 [P] [US2] Create error handling test cases in frontend/src/features/auth/components/LoginForm.test.tsx

### Implementation for User Story 2

- [x] T017 [US2] Add API error handling to useLogin hook in frontend/src/features/auth/hooks/useLogin.ts
- [x] T018 [US2] Create error message display component in frontend/src/features/auth/components/LoginForm.tsx
- [x] T019 [US2] Handle 401 (auth error), 423 (locked), 429 (rate limit) responses
- [x] T020 [US2] Handle network errors with appropriate messages
- [x] T021 [US2] Verify error display tests pass

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - 入力バリデーション (Priority: P2)

**Goal**: 送信前にクライアントサイドバリデーションエラーを表示

**Independent Test**: 空欄や不正な形式のメールアドレスを入力し、即座にバリデーションエラーが表示されることを確認

### Tests for User Story 3

- [x] T022 [P] [US3] Create validation test cases in frontend/src/features/auth/components/LoginForm.test.tsx

### Implementation for User Story 3

- [x] T023 [US3] Enhance Zod schema with validation messages in frontend/src/features/auth/schemas/loginSchema.ts
- [x] T024 [US3] Add field-level error display in frontend/src/features/auth/components/LoginForm.tsx
- [x] T025 [US3] Implement real-time validation on blur/change events
- [x] T026 [US3] Verify validation tests pass

**Checkpoint**: User Stories 1, 2, AND 3 should all work independently

---

## Phase 6: User Story 4 - パスワードマスク表示 (Priority: P3)

**Goal**: パスワード入力時にマスク表示でセキュリティを確保

**Independent Test**: パスワード入力欄に文字を入力し、マスク表示されることを確認

### Implementation for User Story 4

- [x] T027 [US4] Ensure password input uses type="password" in frontend/src/features/auth/components/LoginForm.tsx
- [x] T028 [US4] Add accessibility attributes (aria-label) for password field

**Checkpoint**: All user stories should now be independently functional

---

## Phase 7: User Story 5 - 認証済みリダイレクト (Priority: P2)

**Goal**: 認証済みユーザーがログイン画面にアクセスした場合、ダッシュボードへ自動リダイレクト

**Independent Test**: 認証済み状態でログイン画面にアクセスし、ダッシュボードへリダイレクトされることを確認

### Implementation for User Story 5

- [x] T029 [US5] Create ProtectedRoute component in frontend/src/features/auth/components/ProtectedRoute.tsx
- [x] T030 [US5] Create GuestRoute component (redirect if authenticated) in frontend/src/features/auth/components/GuestRoute.tsx
- [x] T031 [US5] Apply GuestRoute to /login route in frontend/src/app/router.tsx
- [x] T032 [US5] Implement auth state check on app initialization

**Checkpoint**: All features including auth guards are functional

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T033 [P] Create E2E login test in frontend/tests/e2e/login.spec.ts
- [x] T034 [P] Add UI styling and responsive design with Tailwind CSS
- [x] T035 [P] Add accessibility attributes (aria-*, role) for WCAG 2.1 AA compliance
- [x] T036 Run ESLint and Prettier formatting
- [x] T037 Validate quickstart.md manual test scenarios
- [x] T038 Update requirements.md checklist with completion status

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-7)**: All depend on Foundational phase completion
  - US1 and US2 are both P1 - can proceed in parallel
  - US3, US4, US5 can proceed in parallel after P1 stories
- **Polish (Phase 8)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1) - ログイン認証**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1) - 認証エラー表示**: Can start after Foundational - Builds on US1 components
- **User Story 3 (P2) - 入力バリデーション**: Can start after Foundational - Enhances US1 components
- **User Story 4 (P3) - パスワードマスク**: Can start after US1 - Simple enhancement
- **User Story 5 (P2) - 認証済みリダイレクト**: Can start after US1 - Independent auth guard

### Within Each User Story

- Tests SHOULD be written before implementation
- Types/Schemas before hooks
- Hooks before components
- Components before pages
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T002, T003)
- All Foundational tasks marked [P] can run in parallel (T005, T006)
- Once Foundational phase completes:
  - US1 and US2 can start in parallel (both P1)
  - US3, US4, US5 can start in parallel after US1 login component exists
- All tests for a user story marked [P] can run in parallel
- All Polish tasks marked [P] can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Create LoginForm unit test in frontend/src/features/auth/components/LoginForm.test.tsx"
Task: "Create useLogin hook unit test in frontend/src/features/auth/hooks/useLogin.test.ts"

# Then implement sequentially:
Task: "Implement useLogin hook in frontend/src/features/auth/hooks/useLogin.ts"
Task: "Create LoginForm component in frontend/src/features/auth/components/LoginForm.tsx"
Task: "Create LoginPage in frontend/src/features/auth/pages/LoginPage.tsx"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 - ログイン認証
4. **STOP and VALIDATE**: Test login → dashboard flow
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test error handling → Deploy/Demo
4. Add User Story 3 → Test validation → Deploy/Demo
5. Add User Story 4 → Verify password masking → Deploy/Demo
6. Add User Story 5 → Test auth redirects → Deploy/Demo
7. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (ログイン認証)
   - Developer B: User Story 2 (認証エラー表示) after US1 components ready
3. After US1/US2:
   - Developer A: User Story 3 (入力バリデーション)
   - Developer B: User Story 5 (認証済みリダイレクト)
4. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- 既存の books フィーチャーのパターンに従う
- Tailwind CSS でスタイリング
