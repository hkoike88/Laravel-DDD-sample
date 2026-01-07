# Tasks: 蔵書リポジトリ実装

**Input**: Design documents from `/specs/002-book-repository/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/, research.md, quickstart.md

**Tests**: 仕様書（SC-004）で「統合テストカバレッジ90%以上」が要求されているため、テストタスクを含む。

**Organization**: タスクはユーザーストーリー単位でグループ化。各ストーリーは独立してテスト可能。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（US1, US2, US3, US4, US5, US6）
- 説明には正確なファイルパスを含む

## Path Conventions

本プロジェクトは Web アプリケーション構成（backend + frontend）。
ドメイン層のパスは `backend/packages/Domain/Book/` を使用。

---

## Phase 1: Setup（インフラストラクチャ準備）

**Purpose**: データベース基盤とEloquentモデルの作成

- [x] T001 Create books table migration in backend/database/migrations/xxxx_create_books_table.php
- [x] T002 Run migration to create books table with `php artisan migrate`
- [x] T003 [P] Create BookRecord Eloquent model in backend/packages/Domain/Book/Infrastructure/EloquentModels/BookRecord.php
- [x] T004 [P] Create Infrastructure/EloquentModels directory structure in backend/packages/Domain/Book/

---

## Phase 2: Foundational（基盤タスク）

**Purpose**: 全ユーザーストーリーで共有される例外クラスとDTO

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの実装は開始不可

- [x] T005 [P] Create BookNotFoundException in backend/packages/Domain/Book/Domain/Exceptions/BookNotFoundException.php
- [x] T006 [P] Create BookSearchCriteria DTO in backend/packages/Domain/Book/Application/DTO/BookSearchCriteria.php
- [x] T007 [P] Create BookCollection DTO in backend/packages/Domain/Book/Application/DTO/BookCollection.php
- [x] T008 [P] Create DTO directory structure in backend/packages/Domain/Book/Application/DTO/
- [x] T009 Update BookRepositoryInterface with search and count methods in backend/packages/Domain/Book/Domain/Repositories/BookRepositoryInterface.php

**Checkpoint**: 基盤準備完了 - ユーザーストーリーの実装開始可能

---

## Phase 3: User Story 1 - 蔵書の永続化 (Priority: P1) 🎯 MVP

**Goal**: 蔵書エンティティをデータベースに保存し、IDで取得できるようにする

**Independent Test**: 蔵書エンティティを保存し、IDで取得して元のデータと一致することを確認

### Tests for User Story 1

> **NOTE: テストを先に書き、実装前に失敗することを確認**

- [x] T010 [P] [US1] Create EloquentBookRepositoryTest base in backend/tests/Integration/Domain/Book/Repositories/EloquentBookRepositoryTest.php
- [x] T011 [US1] Add save and find tests to EloquentBookRepositoryTest

### Implementation for User Story 1

- [x] T012 [US1] Create EloquentBookRepository with save method in backend/packages/Domain/Book/Application/Repositories/EloquentBookRepository.php
- [x] T013 [US1] Implement find method in EloquentBookRepository
- [x] T014 [US1] Implement findOrNull method in EloquentBookRepository
- [x] T015 [US1] Implement toDomain private method for Eloquent to Domain conversion
- [x] T016 [US1] Update BookServiceProvider to bind BookRepositoryInterface to EloquentBookRepository in backend/packages/Domain/Book/Application/Providers/BookServiceProvider.php
- [x] T017 [US1] Verify tests pass for save and find with `php artisan test tests/Integration/Domain/Book/Repositories/EloquentBookRepositoryTest.php`

**Checkpoint**: User Story 1 完了 - 蔵書の保存と取得が可能、独立してテスト可能

---

## Phase 4: User Story 2 - ISBN による蔵書検索 (Priority: P1)

**Goal**: ISBN番号で蔵書を検索できるようにする（複本対応）

**Independent Test**: 同一ISBNを持つ複数の蔵書を保存し、ISBN検索で全件取得できることを確認

### Tests for User Story 2

- [x] T018 [US2] Add findByIsbn tests to EloquentBookRepositoryTest

### Implementation for User Story 2

- [x] T019 [US2] Implement findByIsbn method in EloquentBookRepository
- [x] T020 [US2] Verify tests pass for findByIsbn with `php artisan test --filter=findByIsbn`

**Checkpoint**: User Story 2 完了 - ISBN検索が機能、独立してテスト可能

---

## Phase 5: User Story 3 - 条件指定による蔵書検索 (Priority: P1)

**Goal**: タイトル・著者・出版社などの条件を組み合わせて蔵書を検索できるようにする

**Independent Test**: 複数の蔵書を登録し、各種条件で検索して期待する結果が得られることを確認

### Tests for User Story 3

- [x] T021 [P] [US3] Create BookSearchCriteriaTest in backend/tests/Unit/Domain/Book/DTO/BookSearchCriteriaTest.php
- [x] T022 [P] [US3] Create BookCollectionTest in backend/tests/Unit/Domain/Book/DTO/BookCollectionTest.php
- [x] T023 [US3] Add search tests to EloquentBookRepositoryTest

### Implementation for User Story 3

- [x] T024 [US3] Implement applySearchCriteria private method in EloquentBookRepository
- [x] T025 [US3] Implement search method with pagination in EloquentBookRepository
- [x] T026 [US3] Verify DTO tests pass with `php artisan test tests/Unit/Domain/Book/DTO/`
- [x] T027 [US3] Verify search tests pass with `php artisan test --filter=search`

**Checkpoint**: User Story 3 完了 - 条件検索とページネーションが機能、独立してテスト可能

---

## Phase 6: User Story 4 - 蔵書情報の更新 (Priority: P2)

**Goal**: 既存の蔵書情報を更新し、変更を永続化できるようにする

**Independent Test**: 蔵書を保存後、情報を更新して再保存し、更新内容が反映されていることを確認

### Tests for User Story 4

- [x] T028 [US4] Add update tests to EloquentBookRepositoryTest

### Implementation for User Story 4

- [x] T029 [US4] Verify save method handles update correctly (upsert logic) in EloquentBookRepository
- [x] T030 [US4] Verify update tests pass with `php artisan test --filter=update`

**Checkpoint**: User Story 4 完了 - 蔵書の更新が機能、独立してテスト可能

---

## Phase 7: User Story 5 - 蔵書の削除 (Priority: P3)

**Goal**: 不要になった蔵書レコードを削除できるようにする

**Independent Test**: 蔵書を保存後に削除し、再検索で取得できないことを確認

### Tests for User Story 5

- [x] T031 [US5] Add delete tests to EloquentBookRepositoryTest

### Implementation for User Story 5

- [x] T032 [US5] Implement delete method in EloquentBookRepository
- [x] T033 [US5] Verify delete tests pass with `php artisan test --filter=delete`

**Checkpoint**: User Story 5 完了 - 蔵書の削除が機能、独立してテスト可能

---

## Phase 8: User Story 6 - 検索結果件数の取得 (Priority: P2)

**Goal**: 検索条件に一致する蔵書の件数を効率的に取得する

**Independent Test**: 複数の蔵書を登録し、条件に一致する件数が正しく返されることを確認

### Tests for User Story 6

- [x] T034 [US6] Add count tests to EloquentBookRepositoryTest

### Implementation for User Story 6

- [x] T035 [US6] Implement count method in EloquentBookRepository
- [x] T036 [US6] Verify count tests pass with `php artisan test --filter=count`

**Checkpoint**: User Story 6 完了 - 件数取得が機能、独立してテスト可能

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: 全体検証、コード品質、ドキュメント整備

- [x] T037 Run full test suite and verify 90%+ coverage with `php artisan test --coverage`
- [x] T038 Run static analysis with Larastan: `./vendor/bin/phpstan analyse`
- [x] T039 [P] Validate implementation against quickstart.md examples
- [x] T040 [P] Verify database indexes are created correctly with `SHOW INDEX FROM books`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - 全ユーザーストーリーをブロック
- **User Stories (Phase 3-8)**: Foundational 完了後
  - US1, US2 は互いに依存しないため並列実行可能
  - US3 は US1（save/find）に軽微に依存
  - US4 は US1（save）に依存
  - US5 は US1（save/find）に依存
  - US6 は US3（検索条件適用）に依存
- **Polish (Phase 9)**: 全ユーザーストーリー完了後

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能 - 他のストーリーに依存しない - **MVP**
- **User Story 2 (P1)**: Foundational 完了後に開始可能 - 他のストーリーに依存しない
- **User Story 3 (P1)**: Foundational 完了後に開始可能 - DTO が Foundational で作成済み
- **User Story 4 (P2)**: US1 の save メソッドを使用 - 軽微な統合あり
- **User Story 5 (P3)**: US1 の save/find メソッドを使用 - 軽微な統合あり
- **User Story 6 (P2)**: US3 の検索条件適用ロジックを再利用

### Within Each User Story

- テストを先に書き、失敗を確認してから実装
- 実装完了後にテスト成功を確認
- 次のストーリーに進む前にチェックポイントを検証

### Parallel Opportunities

**Phase 1 (Setup):**
- T003, T004 は並列実行可能

**Phase 2 (Foundational):**
- T005, T006, T007, T008 は並列実行可能（別ファイル）

**Phase 3-8 (User Stories):**
- US1, US2 は並列実行可能（異なるメソッド）
- 各ストーリーのテストタスクは [P] マーク付きは並列実行可能

**Phase 9 (Polish):**
- T039, T040 は並列実行可能

---

## Parallel Example: User Story 1 & 2

```bash
# 異なる開発者が並列で作業可能:

# Developer A: User Story 1 (永続化)
Task: "Create EloquentBookRepositoryTest base in tests/Integration/Domain/Book/Repositories/"
Task: "Create EloquentBookRepository with save method"
Task: "Implement find and findOrNull methods"

# Developer B: User Story 2 (ISBN検索)
# (注: US1 の基本実装後に開始が望ましい)
Task: "Add findByIsbn tests to EloquentBookRepositoryTest"
Task: "Implement findByIsbn method"
```

---

## Parallel Example: Foundational Phase

```bash
# 4つのタスクを並列で実行可能:

# Terminal 1
Task: "Create BookNotFoundException in Exceptions/"

# Terminal 2
Task: "Create BookSearchCriteria DTO in Application/DTO/"

# Terminal 3
Task: "Create BookCollection DTO in Application/DTO/"

# Terminal 4
Task: "Create DTO directory structure"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup 完了
2. Phase 2: Foundational 完了（CRITICAL - 全ストーリーをブロック）
3. Phase 3: User Story 1 完了
4. **STOP and VALIDATE**: 蔵書の保存と取得を独立してテスト
5. 必要に応じてデモ/レビュー

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → 独立テスト → MVP! 蔵書の永続化が可能
3. User Story 2 追加 → 独立テスト → ISBN 検索追加
4. User Story 3 追加 → 独立テスト → 条件検索とページネーション追加
5. User Story 4 追加 → 独立テスト → 更新機能追加
6. User Story 5 追加 → 独立テスト → 削除機能追加
7. User Story 6 追加 → 独立テスト → 件数取得追加
8. 各ストーリーは既存機能を壊さずに価値を追加

### Full Implementation

すべてのフェーズを順次完了し、最終的に以下を達成:
- EloquentBookRepository（全メソッド実装）
- BookSearchCriteria, BookCollection の DTO
- BookNotFoundException 例外
- 統合テストカバレッジ 90%以上

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベル = タスクと特定のユーザーストーリーの紐付け
- 各ユーザーストーリーは独立して完了・テスト可能
- テストが失敗することを確認してから実装
- 各タスクまたは論理グループ完了後にコミット
- 任意のチェックポイントで停止し、ストーリーを独立して検証可能
- 避けるべき: 曖昧なタスク、同一ファイルの競合、ストーリー間の独立性を壊す依存関係
