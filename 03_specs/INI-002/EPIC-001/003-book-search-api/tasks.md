# Tasks: 蔵書検索API

**Input**: Design documents from `/specs/003-book-search-api/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Constitution Check（CLAUDE.md）にてテストファースト（Pest）が指定されているため、テストタスクを含む。

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Backend**: `backend/packages/Domain/Book/` - DDDパッケージ構造
- **Tests**: `backend/tests/Feature/Book/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Presentation層とUseCase層のディレクトリ構造準備

- [x] T001 Create Presentation layer directory structure in `backend/packages/Domain/Book/Presentation/HTTP/`
- [x] T002 [P] Create Requests directory in `backend/packages/Domain/Book/Presentation/HTTP/Requests/`
- [x] T003 [P] Create Resources directory in `backend/packages/Domain/Book/Presentation/HTTP/Resources/`
- [x] T004 [P] Create UseCases/Queries/SearchBooks directory in `backend/packages/Domain/Book/Application/UseCases/Queries/SearchBooks/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: 全ユーザーストーリーで使用する基盤コンポーネント

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T005 Add isbn attribute to BookSearchCriteria in `backend/packages/Domain/Book/Application/DTO/BookSearchCriteria.php`
- [x] T006 Add ISBN search condition to EloquentBookRepository.applySearchCriteria() in `backend/packages/Domain/Book/Application/Repositories/EloquentBookRepository.php`
- [x] T007 [P] Create SearchBooksQuery DTO in `backend/packages/Domain/Book/Application/UseCases/Queries/SearchBooks/SearchBooksQuery.php`
- [x] T008 Create SearchBooksHandler UseCase in `backend/packages/Domain/Book/Application/UseCases/Queries/SearchBooks/SearchBooksHandler.php`
- [x] T009 [P] Create BookResource API resource in `backend/packages/Domain/Book/Presentation/HTTP/Resources/BookResource.php`
- [x] T010 [P] Create BookCollectionResource API resource in `backend/packages/Domain/Book/Presentation/HTTP/Resources/BookCollectionResource.php`
- [x] T011 Create SearchBooksRequest FormRequest in `backend/packages/Domain/Book/Presentation/HTTP/Requests/SearchBooksRequest.php`
- [x] T012 Create BookController with index method in `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`
- [x] T013 Create routes.php with GET /api/books endpoint in `backend/packages/Domain/Book/Presentation/routes.php`
- [x] T014 Register routes in BookServiceProvider in `backend/packages/Domain/Book/Application/Providers/BookServiceProvider.php`

**Checkpoint**: Foundation ready - API endpoint is functional, user story tests can begin ✅

---

## Phase 3: User Story 1 - キーワード検索 (Priority: P1) 🎯 MVP

**Goal**: タイトルまたは著者名で蔵書を検索し、条件に一致する蔵書一覧を取得

**Independent Test**: タイトル「猫」で検索 → 「吾輩は猫である」が結果に含まれることを確認

### Tests for User Story 1

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T015 [P] [US1] Feature test: Title partial match search in `backend/tests/Feature/Book/SearchBooksTest.php` (test_can_search_books_by_title)
- [x] T016 [P] [US1] Feature test: Author partial match search in `backend/tests/Feature/Book/SearchBooksTest.php` (test_can_search_books_by_author)
- [x] T017 [P] [US1] Feature test: Combined title and author search in `backend/tests/Feature/Book/SearchBooksTest.php` (test_can_search_books_by_title_and_author)

### Implementation for User Story 1

- [x] T018 [US1] Verify title search works in SearchBooksRequest and Handler
- [x] T019 [US1] Verify author search works in SearchBooksRequest and Handler
- [x] T020 [US1] Verify combined search (AND condition) works
- [x] T021 [US1] Run tests and ensure all US1 tests pass

**Checkpoint**: User Story 1 complete - title/author keyword search is functional ✅

---

## Phase 4: User Story 2 - ISBN検索 (Priority: P1)

**Goal**: ISBN番号を指定して特定の蔵書を完全一致検索

**Independent Test**: ISBN「9784003101018」で検索 → 該当蔵書が1件返される

### Tests for User Story 2

- [x] T022 [P] [US2] Feature test: ISBN-13 exact match search in `backend/tests/Feature/Book/SearchBooksTest.php` (test_can_search_books_by_isbn13)
- [x] T023 [P] [US2] Feature test: ISBN-10 exact match search in `backend/tests/Feature/Book/SearchBooksTest.php` (test_can_search_books_by_isbn10)
- [x] T024 [P] [US2] Feature test: Non-existent ISBN returns empty in `backend/tests/Feature/Book/SearchBooksTest.php` (test_search_by_nonexistent_isbn_returns_empty)
- [ ] T025 [P] [US2] Feature test: Invalid ISBN format returns validation error in `backend/tests/Feature/Book/SearchBooksTest.php` (test_invalid_isbn_format_returns_validation_error) - スコープ外

### Implementation for User Story 2

- [x] T026 [US2] Add ISBN validation rule to SearchBooksRequest (ISBN-10/ISBN-13 pattern) - 基本バリデーションは実装済み
- [x] T027 [US2] Verify ISBN search works in Handler via BookSearchCriteria
- [x] T028 [US2] Run tests and ensure all US2 tests pass

**Checkpoint**: User Story 2 complete - ISBN search is functional ✅

---

## Phase 5: User Story 3 - ページネーション (Priority: P2)

**Goal**: 検索結果をページ単位で分割して取得、総件数・総ページ数情報を返却

**Independent Test**: 100件のデータでpage=3, per_page=20 → 41〜60件目が返される

### Tests for User Story 3

- [x] T029 [P] [US3] Feature test: Default pagination (page=1, per_page=20) in `backend/tests/Feature/Book/SearchBooksTest.php` (test_default_pagination)
- [x] T030 [P] [US3] Feature test: Custom page and per_page in `backend/tests/Feature/Book/SearchBooksTest.php` (test_custom_pagination)
- [x] T031 [P] [US3] Feature test: Page exceeds total returns empty in `backend/tests/Feature/Book/SearchBooksTest.php` (test_page_exceeds_total_returns_empty)
- [x] T032 [P] [US3] Feature test: per_page max limit (100) in `backend/tests/Feature/Book/SearchBooksTest.php` (test_per_page_max_limit)
- [x] T033 [P] [US3] Feature test: Invalid per_page returns validation error in `backend/tests/Feature/Book/SearchBooksTest.php` (test_invalid_per_page_returns_validation_error)

### Implementation for User Story 3

- [x] T034 [US3] Add page/per_page validation rules to SearchBooksRequest
- [x] T035 [US3] Verify pagination meta in BookCollectionResource (total, page, per_page, last_page)
- [x] T036 [US3] Run tests and ensure all US3 tests pass

**Checkpoint**: User Story 3 complete - pagination is functional ✅

---

## Phase 6: User Story 4 - 全件取得 (Priority: P2)

**Goal**: 検索条件なしで全蔵書をページネーション付きで取得

**Independent Test**: パラメータなしでGET /api/books → 全蔵書がページネーションされて返される

### Tests for User Story 4

- [x] T037 [P] [US4] Feature test: No search params returns all books in `backend/tests/Feature/Book/SearchBooksTest.php` (test_no_params_returns_all_books)
- [x] T038 [P] [US4] Feature test: Empty result returns empty array in `backend/tests/Feature/Book/SearchBooksTest.php` (test_empty_database_returns_empty_array)

### Implementation for User Story 4

- [x] T039 [US4] Verify no-params request works correctly
- [x] T040 [US4] Run tests and ensure all US4 tests pass

**Checkpoint**: User Story 4 complete - all books listing is functional ✅

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: エッジケース対応とセキュリティ強化

- [x] T041 [P] Feature test: SQL injection prevention in `backend/tests/Feature/Book/SearchBooksTest.php` (test_sql_injection_prevention)
- [x] T042 [P] Feature test: Long search string validation in `backend/tests/Feature/Book/SearchBooksTest.php` (test_long_search_string_validation)
- [x] T043 Add max length validation for title/author in SearchBooksRequest
- [x] T044 Run full test suite and ensure all tests pass
- [x] T045 Run quickstart.md validation (curl commands)
- [x] T046 Update documentation comments per CLAUDE.md guidelines

**Checkpoint**: Phase 7 complete - Edge cases handled ✅

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-6)**: All depend on Foundational phase completion
  - US1 and US2 can proceed in parallel (both P1)
  - US3 and US4 can proceed in parallel (both P2)
- **Polish (Phase 7)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational - No dependencies on other stories
- **User Story 2 (P1)**: Can start after Foundational - No dependencies on other stories
- **User Story 3 (P2)**: Can start after Foundational - Independent (pagination is already in foundation)
- **User Story 4 (P2)**: Can start after Foundational - Independent

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Implementation tasks depend on tests being written
- Run tests to verify story completion

### Parallel Opportunities

- Phase 1: T002, T003, T004 can run in parallel
- Phase 2: T007, T009, T010 can run in parallel
- Phase 3: T015, T016, T017 (tests) can run in parallel
- Phase 4: T022, T023, T024, T025 (tests) can run in parallel
- Phase 5: T029, T030, T031, T032, T033 (tests) can run in parallel
- Phase 6: T037, T038 (tests) can run in parallel
- Phase 7: T041, T042 (tests) can run in parallel

---

## Parallel Example: Phase 2 (Foundational)

```bash
# Launch parallel tasks:
Task: "Create SearchBooksQuery DTO" (T007)
Task: "Create BookResource API resource" (T009)
Task: "Create BookCollectionResource API resource" (T010)

# Sequential tasks after parallel completion:
Task: "Create SearchBooksHandler UseCase" (T008) - depends on T007
Task: "Create BookController" (T012) - depends on T009, T010, T011
```

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Feature test: Title partial match search" (T015)
Task: "Feature test: Author partial match search" (T016)
Task: "Feature test: Combined title and author search" (T017)

# Implementation after tests written:
Task: "Verify title search works" (T018)
Task: "Verify author search works" (T019)
Task: "Verify combined search works" (T020)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Keyword Search)
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready - users can search by title/author

### Incremental Delivery

1. Complete Setup + Foundational → API endpoint is live
2. Add User Story 1 → Test independently → Deploy (MVP - keyword search works!)
3. Add User Story 2 → Test independently → Deploy (ISBN search added)
4. Add User Story 3 → Test independently → Deploy (pagination enhanced)
5. Add User Story 4 → Test independently → Deploy (all books listing)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (keyword search)
   - Developer B: User Story 2 (ISBN search)
3. After P1 stories complete:
   - Developer A: User Story 3 (pagination)
   - Developer B: User Story 4 (all books)
4. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- All tests use Pest framework per project standards
