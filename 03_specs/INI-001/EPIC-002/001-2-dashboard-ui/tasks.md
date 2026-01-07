# Tasks: ダッシュボード画面

**Input**: Design documents from `/specs/004-dashboard-ui/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テストは既存インフラ（Vitest, Playwright）を活用して作成

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `frontend/src/` at repository root
- DDD フィーチャー構成: `frontend/src/features/dashboard/`
- 共通レイアウト: `frontend/src/components/layout/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: ダッシュボードフィーチャーのディレクトリ構成と型定義

- [ ] T001 Create dashboard feature directory structure in frontend/src/features/dashboard/
- [ ] T002 [P] Create type definitions in frontend/src/features/dashboard/types/menu.ts
- [ ] T003 [P] Create layout directory structure in frontend/src/components/layout/

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: 共通レイアウトコンポーネントとログアウト機能（全ユーザーストーリーの前提条件）

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T004 Implement Footer component in frontend/src/components/layout/Footer.tsx
- [ ] T005 [P] Create useLogout hook in frontend/src/features/auth/hooks/useLogout.ts
- [ ] T006 [P] Create placeholder pages for menu destinations in frontend/src/pages/
- [ ] T007 Add new routes to frontend/src/app/router.tsx (/books, /loans/*, /users, /reservations)

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - 業務メニューへのアクセス (Priority: P1) 🎯 MVP

**Goal**: 職員がダッシュボードで業務メニューを確認し、各機能に遷移できる

**Independent Test**: ダッシュボード画面を表示し、メニューカードをクリックして該当画面に遷移できることを確認

### Implementation for User Story 1

- [ ] T008 [P] [US1] Create MenuCard component in frontend/src/features/dashboard/components/MenuCard.tsx
- [ ] T009 [P] [US1] Create MenuGrid component in frontend/src/features/dashboard/components/MenuGrid.tsx
- [ ] T010 [US1] Create DashboardPage with menu grid in frontend/src/features/dashboard/pages/DashboardPage.tsx
- [ ] T011 [US1] Update router to use new DashboardPage in frontend/src/app/router.tsx
- [ ] T012 [US1] Handle disabled menu items with "準備中" message display

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - ユーザー情報の表示 (Priority: P1)

**Goal**: ダッシュボードでログイン中の職員名を表示

**Independent Test**: ダッシュボード画面でログイン中の職員名が表示されることを確認

### Implementation for User Story 2

- [ ] T013 [P] [US2] Create WelcomeMessage component in frontend/src/features/dashboard/components/WelcomeMessage.tsx
- [ ] T014 [US2] Integrate WelcomeMessage with authStore in DashboardPage
- [ ] T015 [US2] Add current date display to WelcomeMessage

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - ログアウト (Priority: P1)

**Goal**: 職員がログアウトしてセッションを終了できる

**Independent Test**: ログアウトボタンをクリックしてログイン画面に遷移し、再度ダッシュボードにアクセスできないことを確認

### Implementation for User Story 3

- [ ] T016 [US3] Implement Header component with logout button in frontend/src/components/layout/Header.tsx
- [ ] T017 [US3] Integrate useLogout hook with Header component
- [ ] T018 [US3] Add loading state during logout process
- [ ] T019 [US3] Handle logout errors gracefully (clear local state and redirect)

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently

---

## Phase 6: User Story 4 - ヘッダーナビゲーション (Priority: P2)

**Goal**: ヘッダーのナビゲーションから主要機能に素早くアクセスできる

**Independent Test**: ヘッダーのナビゲーションリンクをクリックして該当画面に遷移できることを確認

### Implementation for User Story 4

- [ ] T020 [US4] Add navigation links to Header component
- [ ] T021 [US4] Implement logo click to return to dashboard
- [ ] T022 [US4] Add responsive mobile menu (hamburger) to Header
- [ ] T023 [US4] Highlight current page in navigation

**Checkpoint**: All user stories should now be independently functional

---

## Phase 7: Integration & Layout

**Purpose**: MainLayout の統合と全画面への適用

- [ ] T024 Create MainLayout component (Header + children + Footer) in frontend/src/components/layout/MainLayout.tsx
- [ ] T025 Apply MainLayout to DashboardPage
- [ ] T026 Apply MainLayout to all placeholder pages

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: スタイリング、アクセシビリティ、テスト

- [ ] T027 [P] Add responsive grid layout to MenuGrid (1-2-3 columns)
- [ ] T028 [P] Add Tailwind CSS styling to all new components
- [ ] T029 [P] Add accessibility attributes (aria-*, role) for WCAG 2.1 AA
- [ ] T030 [P] Create E2E test for dashboard flow in frontend/tests/e2e/dashboard.spec.ts
- [ ] T031 Run ESLint and Prettier formatting
- [ ] T032 Validate quickstart.md manual test scenarios

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-6)**: All depend on Foundational phase completion
  - US1, US2, US3 are all P1 - can proceed in parallel
  - US4 is P2 - can proceed after P1 stories
- **Integration (Phase 7)**: Depends on all user stories being complete
- **Polish (Phase 8)**: Depends on Integration completion

### User Story Dependencies

- **User Story 1 (P1) - 業務メニューへのアクセス**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1) - ユーザー情報の表示**: Can start after Foundational - Integrates with US1's DashboardPage
- **User Story 3 (P1) - ログアウト**: Can start after Foundational - Requires Header component
- **User Story 4 (P2) - ヘッダーナビゲーション**: Can start after US3 - Extends Header component

### Within Each User Story

- Types/Models before components
- Components before pages
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T002, T003)
- All Foundational tasks marked [P] can run in parallel (T005, T006)
- Once Foundational phase completes:
  - US1 and US2 can start in parallel (different components)
  - US3 requires Header, which US4 extends
- All Polish tasks marked [P] can run in parallel

---

## Parallel Example: User Story 1 + User Story 2

```bash
# Launch US1 and US2 component tasks together:
Task: "Create MenuCard component in frontend/src/features/dashboard/components/MenuCard.tsx"
Task: "Create WelcomeMessage component in frontend/src/features/dashboard/components/WelcomeMessage.tsx"

# Then integrate sequentially:
Task: "Create DashboardPage with menu grid and welcome message"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 - 業務メニューへのアクセス
4. **STOP and VALIDATE**: メニューカードから各画面への遷移を確認
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test menu navigation → Deploy/Demo (MVP!)
3. Add User Story 2 → Test user info display → Deploy/Demo
4. Add User Story 3 → Test logout flow → Deploy/Demo
5. Add User Story 4 → Test header navigation → Deploy/Demo
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (MenuCard, MenuGrid, DashboardPage)
   - Developer B: User Story 2 (WelcomeMessage) + User Story 3 (Header, Logout)
3. After US3:
   - Developer A: User Story 4 (Header navigation extension)
   - Developer B: Integration (MainLayout)
4. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- 既存の auth フィーチャーのパターンに従う
- Tailwind CSS でスタイリング
