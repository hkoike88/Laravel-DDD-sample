# Tasks: 蔵書エンティティ・Value Object 設計

**Input**: Design documents from `/specs/001-book-entity-design/`
**Prerequisites**: plan.md, spec.md, data-model.md, research.md, quickstart.md

**Tests**: 仕様書（SC-002）で「単体テストカバレッジ100%」が要求されているため、テストタスクを含む。

**Organization**: タスクはユーザーストーリー単位でグループ化。各ストーリーは独立してテスト可能。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（US1, US2, US3）
- 説明には正確なファイルパスを含む

## Path Conventions

本プロジェクトは Web アプリケーション構成（backend + frontend）。
ドメイン層のパスは `backend/packages/Domain/Book/` を使用。

---

## Phase 1: Setup（プロジェクト初期化）

**Purpose**: ドメインパッケージの基盤構造を作成

- [x] T001 Create domain package directory structure in backend/packages/Domain/Book/
- [x] T002 Add symfony/uid dependency for ULID generation with `composer require symfony/uid`
- [x] T003 [P] Configure PSR-4 autoloading for Packages namespace in backend/composer.json
- [x] T004 [P] Create test directory structure in backend/tests/Unit/Domain/Book/

---

## Phase 2: Foundational（基盤タスク）

**Purpose**: 全ユーザーストーリーで共有される例外クラスとインターフェースを作成

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの実装は開始不可

- [x] T005 [P] Create EmptyBookTitleException in backend/packages/Domain/Book/Domain/Exceptions/EmptyBookTitleException.php
- [x] T006 [P] Create InvalidISBNException in backend/packages/Domain/Book/Domain/Exceptions/InvalidISBNException.php
- [x] T007 [P] Create InvalidBookStatusTransitionException in backend/packages/Domain/Book/Domain/Exceptions/InvalidBookStatusTransitionException.php

**Checkpoint**: 基盤準備完了 - ユーザーストーリーの実装開始可能

---

## Phase 3: User Story 1 - 蔵書情報の管理 (Priority: P1) 🎯 MVP

**Goal**: Book エンティティと BookId Value Object を実装し、蔵書の基本情報を管理可能にする

**Independent Test**: 蔵書エンティティを作成し、各属性（タイトル、著者、ISBN等）が正しく保持されることを単体テストで確認

### Tests for User Story 1

> **NOTE: テストを先に書き、実装前に失敗することを確認**

- [x] T008 [P] [US1] Create BookIdTest in backend/tests/Unit/Domain/Book/ValueObjects/BookIdTest.php
- [x] T009 [P] [US1] Create BookTest in backend/tests/Unit/Domain/Book/Model/BookTest.php

### Implementation for User Story 1

- [x] T010 [US1] Implement BookId Value Object in backend/packages/Domain/Book/Domain/ValueObjects/BookId.php
- [x] T011 [US1] Implement Book entity in backend/packages/Domain/Book/Domain/Model/Book.php
- [x] T012 [US1] Verify tests pass for BookId and Book with `php artisan test tests/Unit/Domain/Book`

**Checkpoint**: User Story 1 完了 - 蔵書の基本情報管理が可能、独立してテスト可能

---

## Phase 4: User Story 2 - ISBN バリデーション (Priority: P1)

**Goal**: ISBN Value Object を実装し、ISBN-10/ISBN-13 の国際標準形式バリデーションを提供

**Independent Test**: 有効な ISBN が受理され、不正な ISBN（形式エラー、チェックディジット不正）が拒否されることを確認

### Tests for User Story 2

- [x] T013 [P] [US2] Create ISBNTest in backend/tests/Unit/Domain/Book/ValueObjects/ISBNTest.php

### Implementation for User Story 2

- [x] T014 [US2] Implement ISBN Value Object with validation in backend/packages/Domain/Book/Domain/ValueObjects/ISBN.php
- [x] T015 [US2] Verify tests pass for ISBN with `php artisan test tests/Unit/Domain/Book/ValueObjects/ISBNTest.php`

**Checkpoint**: User Story 2 完了 - ISBN バリデーションが機能、独立してテスト可能

---

## Phase 5: User Story 3 - 蔵書ステータス管理 (Priority: P1)

**Goal**: BookStatus Value Object を実装し、状態遷移ルール（available/borrowed/reserved）を管理

**Independent Test**: 許可された状態遷移のみが成功し、不正な遷移（例：available→return）が拒否されることを確認

### Tests for User Story 3

- [x] T016 [P] [US3] Create BookStatusTest in backend/tests/Unit/Domain/Book/ValueObjects/BookStatusTest.php

### Implementation for User Story 3

- [x] T017 [US3] Implement BookStatus Value Object in backend/packages/Domain/Book/Domain/ValueObjects/BookStatus.php
- [x] T018 [US3] Update Book entity with status transition methods in backend/packages/Domain/Book/Domain/Model/Book.php
- [x] T019 [US3] Add status transition tests to BookTest in backend/tests/Unit/Domain/Book/Model/BookTest.php
- [x] T020 [US3] Verify all tests pass with `php artisan test tests/Unit/Domain/Book`

**Checkpoint**: User Story 3 完了 - 蔵書ステータス管理が機能、独立してテスト可能

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: リポジトリインターフェース定義、全体検証、ドキュメント整備

- [x] T021 [P] Create BookRepositoryInterface in backend/packages/Domain/Book/Domain/Repositories/BookRepositoryInterface.php
- [x] T022 [P] Create BookServiceProvider in backend/packages/Domain/Book/Application/Providers/BookServiceProvider.php
- [x] T023 Register BookServiceProvider in backend/bootstrap/providers.php
- [x] T024 Run full test suite and verify 100% coverage with `php artisan test --coverage`
- [x] T025 Validate implementation against quickstart.md examples

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - 全ユーザーストーリーをブロック
- **User Stories (Phase 3-5)**: Foundational 完了後
  - US1, US2, US3 は互いに依存しないため並列実行可能
  - または優先度順に順次実行（P1 → P1 → P1）
- **Polish (Phase 6)**: 全ユーザーストーリー完了後

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能 - 他のストーリーに依存しない
- **User Story 2 (P1)**: Foundational 完了後に開始可能 - 他のストーリーに依存しない
- **User Story 3 (P1)**: Foundational 完了後に開始可能 - Book エンティティの状態遷移メソッド追加のため US1 と軽微な統合あり

### Within Each User Story

- テストを先に書き、失敗を確認してから実装
- Value Object → Entity の順序
- 実装完了後にテスト成功を確認
- 次のストーリーに進む前にチェックポイントを検証

### Parallel Opportunities

**Phase 1 (Setup):**
- T003, T004 は並列実行可能

**Phase 2 (Foundational):**
- T005, T006, T007 は並列実行可能（別ファイル）

**Phase 3-5 (User Stories):**
- US1, US2, US3 の各テストタスクは並列実行可能
- 異なるストーリーは別開発者が並列で担当可能

**Phase 6 (Polish):**
- T021, T022 は並列実行可能

---

## Parallel Example: User Story 2 & 3

```bash
# 異なる開発者が並列で作業可能:

# Developer A: User Story 2 (ISBN)
Task: "Create ISBNTest in backend/tests/Unit/Domain/Book/ValueObjects/ISBNTest.php"
Task: "Implement ISBN Value Object in backend/packages/Domain/Book/Domain/ValueObjects/ISBN.php"

# Developer B: User Story 3 (BookStatus)
Task: "Create BookStatusTest in backend/tests/Unit/Domain/Book/ValueObjects/BookStatusTest.php"
Task: "Implement BookStatus Value Object in backend/packages/Domain/Book/Domain/ValueObjects/BookStatus.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup 完了
2. Phase 2: Foundational 完了（CRITICAL - 全ストーリーをブロック）
3. Phase 3: User Story 1 完了
4. **STOP and VALIDATE**: Book エンティティと BookId を独立してテスト
5. 必要に応じてデモ/レビュー

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → 独立テスト → MVP!
3. User Story 2 追加 → 独立テスト → ISBN バリデーション追加
4. User Story 3 追加 → 独立テスト → ステータス管理追加
5. 各ストーリーは既存機能を壊さずに価値を追加

### Full Implementation

すべてのフェーズを順次完了し、最終的に以下を達成:
- Book エンティティ（全属性、状態遷移メソッド）
- BookId, ISBN, BookStatus の Value Object
- 単体テストカバレッジ 100%
- BookRepositoryInterface（後続タスクで実装）

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベル = タスクと特定のユーザーストーリーの紐付け
- 各ユーザーストーリーは独立して完了・テスト可能
- テストが失敗することを確認してから実装
- 各タスクまたは論理グループ完了後にコミット
- 任意のチェックポイントで停止し、ストーリーを独立して検証可能
- 避けるべき: 曖昧なタスク、同一ファイルの競合、ストーリー間の独立性を壊す依存関係
