# Tasks: 蔵書検索画面

**Input**: Design documents from `/specs/004-book-search-ui/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/api-usage.md

**Tests**: テストは後続タスクで追加予定（Vitest未導入のためテストタスクは含まず）

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `backend/src/`, `frontend/src/`
- Paths follow plan.md structure: `frontend/src/features/books/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure for book search feature

- [x] T001 Create books feature directory structure in frontend/src/features/books/
- [x] T002 [P] Create Axios client instance in frontend/src/lib/axios.ts
- [x] T003 [P] Create book type definitions in frontend/src/features/books/types/book.ts

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T004 Implement useBookSearch hook in frontend/src/features/books/hooks/useBookSearch.ts
- [x] T005 Create book API client in frontend/src/features/books/api/bookApi.ts
- [x] T006 Add /books route to router configuration in frontend/src/app/router.tsx

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - キーワード検索と結果表示 (Priority: P1) 🎯 MVP

**Goal**: タイトルまたは著者名を入力して蔵書を検索し、検索結果を一覧で確認できる

**Independent Test**: 検索フォームにキーワードを入力し、検索ボタンをクリックすると、該当する蔵書が一覧表示されることを確認できる

### Implementation for User Story 1

- [x] T007 [P] [US1] Create BookSearchForm component in frontend/src/features/books/components/BookSearchForm.tsx
- [x] T008 [P] [US1] Create BookSearchResults component (table structure) in frontend/src/features/books/components/BookSearchResults.tsx
- [x] T009 [US1] Create BookSearchPage component in frontend/src/features/books/pages/BookSearchPage.tsx
- [x] T010 [US1] Implement loading state display in BookSearchPage
- [x] T011 [US1] Implement search results count display in BookSearchPage

**Checkpoint**: User Story 1 is fully functional - keyword search and results display works independently

---

## Phase 4: User Story 2 - 蔵書状態の視覚的確認 (Priority: P1)

**Goal**: 検索結果で各蔵書の貸出状態を色分けバッジで一目で確認できる

**Independent Test**: 検索結果で各蔵書の状態が色分けされたバッジで表示され、貸出可能かどうかが一目でわかることを確認できる

### Implementation for User Story 2

- [x] T012 [US2] Create BookStatusBadge component in frontend/src/features/books/components/BookStatusBadge.tsx
- [x] T013 [US2] Integrate BookStatusBadge into BookSearchResults component in frontend/src/features/books/components/BookSearchResults.tsx

**Checkpoint**: User Stories 1 AND 2 are both working - search with status badges is complete

---

## Phase 5: User Story 3 - ISBN検索 (Priority: P2)

**Goal**: ISBNを入力して特定の蔵書を正確に検索できる

**Independent Test**: ISBN欄に有効なISBNを入力して検索すると、該当する蔵書のみが表示されることを確認できる

### Implementation for User Story 3

- [x] T014 [US3] Add ISBN input field to BookSearchForm component in frontend/src/features/books/components/BookSearchForm.tsx
- [x] T015 [US3] Update BookSearchForm validation schema for ISBN field in frontend/src/features/books/components/BookSearchForm.tsx

**Checkpoint**: ISBN search is now functional alongside keyword search

---

## Phase 6: User Story 4 - ページネーション (Priority: P2)

**Goal**: 検索結果が多い場合でもページ分割で効率的に閲覧できる

**Independent Test**: 検索結果が複数ページにまたがる場合、ページ番号をクリックして他のページの結果を表示できることを確認できる

### Implementation for User Story 4

- [x] T016 [US4] Create Pagination component in frontend/src/features/books/components/Pagination.tsx
- [x] T017 [US4] Integrate Pagination with BookSearchPage in frontend/src/features/books/pages/BookSearchPage.tsx
- [x] T018 [US4] Update useBookSearch hook to handle page parameter in frontend/src/features/books/hooks/useBookSearch.ts

**Checkpoint**: Pagination is fully functional with search results

---

## Phase 7: User Story 5 - 検索結果なし時のフィードバック (Priority: P2)

**Goal**: 検索結果が0件の場合に適切なメッセージを表示する

**Independent Test**: 該当する蔵書がない検索条件で検索すると、適切なメッセージが表示されることを確認できる

### Implementation for User Story 5

- [x] T019 [US5] Add empty state message display to BookSearchResults in frontend/src/features/books/components/BookSearchResults.tsx
- [x] T020 [US5] Add search hint text for empty results in frontend/src/features/books/components/BookSearchResults.tsx

**Checkpoint**: Empty state feedback is complete

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T021 [P] Add error handling and error message display to BookSearchPage in frontend/src/features/books/pages/BookSearchPage.tsx
- [x] T022 [P] Add network error retry functionality to BookSearchPage in frontend/src/features/books/pages/BookSearchPage.tsx
- [x] T023 Run quickstart.md validation - verify all features work as documented

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-7)**: All depend on Foundational phase completion
  - US1 and US2 are P1 priority - should complete first
  - US3, US4, US5 are P2 priority - can proceed after US1/US2
- **Polish (Phase 8)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1)**: Depends on US1 (integrates status badge into search results table)
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) - Extends search form
- **User Story 4 (P2)**: Can start after Foundational (Phase 2) - Independent pagination component
- **User Story 5 (P2)**: Depends on US1 (extends search results component)

### Within Each User Story

- Components/hooks before page integration
- Core implementation before enhancement
- Story complete before moving to next priority

### Parallel Opportunities

- **Phase 1**: T002, T003 can run in parallel
- **Phase 3 (US1)**: T007, T008 can run in parallel
- **Phase 8**: T021, T022 can run in parallel

---

## Parallel Example: Setup Phase

```bash
# Launch all parallel tasks in Setup together:
Task: "Create Axios client instance in frontend/src/lib/axios.ts"
Task: "Create book type definitions in frontend/src/features/books/types/book.ts"
```

## Parallel Example: User Story 1

```bash
# Launch all parallel tasks for US1 together:
Task: "Create BookSearchForm component in frontend/src/features/books/components/BookSearchForm.tsx"
Task: "Create BookSearchResults component in frontend/src/features/books/components/BookSearchResults.tsx"
```

---

## Implementation Strategy

### MVP First (User Story 1 + 2 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (keyword search + results display)
4. Complete Phase 4: User Story 2 (status badges)
5. **STOP and VALIDATE**: Test search with status badges independently
6. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 + 2 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 3 (ISBN) → Test independently → Deploy/Demo
4. Add User Story 4 (Pagination) → Test independently → Deploy/Demo
5. Add User Story 5 (Empty state) → Test independently → Deploy/Demo
6. Each story adds value without breaking previous stories

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- テストは後続タスクで追加予定（Vitest未導入）
