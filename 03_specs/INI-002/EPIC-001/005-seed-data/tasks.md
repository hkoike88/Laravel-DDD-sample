# Tasks: シードデータ投入

**Input**: Design documents from `/specs/005-seed-data/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/

**Tests**: テストファイルがplan.mdに記載されているため、テストタスクを含む

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `backend/` at repository root
- Paths follow Laravel project structure

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

> このフェーズはスキップ - 既存のLaravelプロジェクト構造を使用

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T001 BookFactoryを作成（Faker日本語ローカライズ対応） in backend/database/factories/BookFactory.php
- [x] T002 DatabaseSeederにBookSeederの呼び出しを追加 in backend/database/seeders/DatabaseSeeder.php

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - コマンドでサンプルデータを投入する (Priority: P1) 🎯 MVP

**Goal**: シードコマンドを実行することで、事前定義された100件以上のサンプル蔵書データをデータベースに投入する

**Independent Test**: `php artisan db:seed --class=BookSeeder` を実行し、データベースに100件以上の蔵書データが投入されていることを確認

### Tests for User Story 1

- [x] T003 [P] [US1] BookSeederのFeatureテストを作成 in backend/tests/Feature/Seed/BookSeederTest.php

### Implementation for User Story 1

- [x] T004 [P] [US1] サンプル蔵書CSVファイルを作成（100件以上、日本語古典文学） in backend/storage/app/sample_books.csv
- [x] T005 [US1] BookSeederを実装（CSVからデータ読み込み、重複ISBNスキップ） in backend/database/seeders/BookSeeder.php
- [x] T006 [US1] BookSeederTest を実行して動作確認

**Checkpoint**: User Story 1 完了 - `php artisan db:seed --class=BookSeeder` で100件以上のデータ投入可能

---

## Phase 4: User Story 2 - 外部ファイルからデータをインポートする (Priority: P2)

**Goal**: CSVファイルから蔵書データをインポートし、バリデーション結果をレポートする

**Independent Test**: `php artisan import:books storage/app/books.csv` を実行し、CSVファイルのデータがデータベースに投入されることを確認

### Tests for User Story 2

- [x] T007 [P] [US2] ImportBooksCommandのFeatureテストを作成 in backend/tests/Feature/Seed/ImportBooksCommandTest.php

### Implementation for User Story 2

- [x] T008 [US2] ImportBooksCommandを実装（CSVパース、バリデーション） in backend/app/Console/Commands/ImportBooksCommand.php
- [x] T009 [US2] ISBN-13チェックディジット検証ロジックを実装 in backend/app/Console/Commands/ImportBooksCommand.php
- [x] T010 [US2] バッチ処理（100件単位）と進捗表示を実装 in backend/app/Console/Commands/ImportBooksCommand.php
- [x] T011 [US2] エラーレポート出力を実装（スキップ行番号とエラー内容） in backend/app/Console/Commands/ImportBooksCommand.php
- [x] T012 [US2] --dry-run オプションを実装 in backend/app/Console/Commands/ImportBooksCommand.php
- [x] T013 [US2] ImportBooksCommandTest を実行して動作確認

**Checkpoint**: User Story 2 完了 - CSVインポートコマンドが動作し、バリデーションエラーをレポート

---

## Phase 5: User Story 3 - ランダムなテストデータを生成する (Priority: P3)

**Goal**: 指定件数のランダムな蔵書データを生成し、大量データでのテストを可能にする

**Independent Test**: `php artisan book:generate 500` を実行し、500件のランダムデータがデータベースに投入されることを確認

### Tests for User Story 3

- [x] T014 [P] [US3] BookFactoryのUnitテストを作成 in backend/tests/Unit/Domain/Book/BookFactoryTest.php
- [x] T015 [P] [US3] GenerateBooksCommandのFeatureテストを作成 in backend/tests/Feature/Seed/GenerateBooksCommandTest.php

### Implementation for User Story 3

- [x] T016 [US3] GenerateBooksCommandを実装 in backend/app/Console/Commands/GenerateBooksCommand.php
- [x] T017 [US3] --status オプションを実装（状態指定生成） in backend/app/Console/Commands/GenerateBooksCommand.php
- [x] T018 [US3] 件数上限チェック（最大10,000件）を実装 in backend/app/Console/Commands/GenerateBooksCommand.php
- [x] T019 [US3] 生成結果サマリー出力を実装（状態別件数表示） in backend/app/Console/Commands/GenerateBooksCommand.php
- [x] T020 [US3] BookFactoryTest と GenerateBooksCommandTest を実行して動作確認

**Checkpoint**: User Story 3 完了 - ランダムデータ生成コマンドが動作

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T021 [P] quickstart.md の手順を実際に実行して検証
- [x] T022 [P] 全テストを実行して合格確認 (`./vendor/bin/pest`) - 186 passed
- [x] T023 コードスタイル確認 (`./vendor/bin/pint`) - 26 style issues fixed

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: スキップ（既存プロジェクト）
- **Foundational (Phase 2)**: No dependencies - BookFactoryが全ストーリーの基盤
- **User Story 1 (Phase 3)**: Depends on Phase 2 (BookFactory)
- **User Story 2 (Phase 4)**: Depends on Phase 2 only - US1と独立
- **User Story 3 (Phase 5)**: Depends on Phase 2 (BookFactory) - US1/US2と独立
- **Polish (Phase 6)**: Depends on all user stories being complete

### User Story Dependencies

```text
Phase 2: Foundational
    │
    ├──> Phase 3: User Story 1 (P1) - BookSeeder
    │
    ├──> Phase 4: User Story 2 (P2) - ImportBooksCommand
    │
    └──> Phase 5: User Story 3 (P3) - GenerateBooksCommand
                    │
                    v
              Phase 6: Polish
```

### Parallel Opportunities

- **Phase 2**: T001, T002 は順次実行（T002がT001に依存しないが、DatabaseSeederの整合性のため）
- **Phase 3**: T003, T004 は並列実行可能（異なるファイル）
- **Phase 4**: T007 は実装前に並列実行可能
- **Phase 5**: T014, T015 は並列実行可能（異なるファイル）
- **Phase 6**: T021, T022, T023 は並列実行可能

---

## Parallel Example: User Story 1

```bash
# Launch tests and sample data creation in parallel:
Task: "BookSeederのFeatureテストを作成 in backend/tests/Feature/Seed/BookSeederTest.php"
Task: "サンプル蔵書CSVファイルを作成 in backend/storage/app/sample_books.csv"

# Then implement seeder (depends on both):
Task: "BookSeederを実装 in backend/database/seeders/BookSeeder.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 2: Foundational (BookFactory)
2. Complete Phase 3: User Story 1 (BookSeeder)
3. **STOP and VALIDATE**: `php artisan db:seed --class=BookSeeder` で100件投入確認
4. Deploy/demo if ready

### Incremental Delivery

1. Complete Phase 2 → Foundation ready
2. Add User Story 1 → Test independently → MVP完了！
3. Add User Story 2 → CSVインポート機能追加
4. Add User Story 3 → ランダム生成機能追加
5. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Phase 2 together (T001, T002)
2. Once Phase 2 is done:
   - Developer A: User Story 1 (BookSeeder)
   - Developer B: User Story 2 (ImportBooksCommand)
   - Developer C: User Story 3 (GenerateBooksCommand)
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- 既存のBookエンティティとリポジトリを使用（スキーマ変更なし）
