# Tasks: 蔵書登録API実装

**Input**: Design documents from `/specs/001-book-registration-api/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Constitution Checkで「テストファースト」が必須のため、テストタスクを含みます。

**Organization**: タスクはユーザーストーリーごとにグループ化され、独立した実装・テストが可能です。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: 所属するユーザーストーリー（US1, US2, US3）
- 説明には正確なファイルパスを含む

## Path Conventions

- **Backend**: `backend/packages/Domain/Book/`
- **Tests**: `backend/tests/`

---

## Phase 1: Setup（共有インフラストラクチャ）

**Purpose**: 新規ディレクトリ構造の作成

- [x] T001 Commands/CreateBook ディレクトリを作成 `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/`

---

## Phase 2: Foundational（基盤タスク）

**Purpose**: 全ユーザーストーリーの前提となるコア実装

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの作業は開始不可

- [x] T002 CreateBookCommand DTO を作成 `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/CreateBookCommand.php`
- [x] T003 CreateBookHandler ユースケースを作成 `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/CreateBookHandler.php`
- [x] T004 CreateBookRequest FormRequest を作成 `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php`
- [x] T005 BookController に store() メソッドを追加 `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`
- [x] T006 POST /api/books ルートを追加 `backend/packages/Domain/Book/Presentation/routes.php`
- [x] T007 BookServiceProvider に CreateBookHandler DI 登録を追加 `backend/packages/Domain/Book/Application/Providers/BookServiceProvider.php`

**Checkpoint**: 基盤完了 - ユーザーストーリーの実装開始可能

---

## Phase 3: User Story 1 - 図書情報の登録 (Priority: P1) 🎯 MVP

**Goal**: 図書館職員が新規図書をシステムに登録できる

**Independent Test**: タイトルを入力して登録すると、蔵書が正常に登録され、登録完了レスポンスが返る

### Tests for User Story 1 ⚠️

> **NOTE: これらのテストを先に書き、実装前に FAIL することを確認**

- [x] T008 [P] [US1] Feature テスト: タイトルのみで登録成功（201）`backend/tests/Feature/Book/CreateBookTest.php`
- [x] T009 [P] [US1] Feature テスト: 全項目入力で登録成功（201）`backend/tests/Feature/Book/CreateBookTest.php`
- [x] T010 [P] [US1] Feature テスト: ISBN付きで登録成功（201）`backend/tests/Feature/Book/CreateBookTest.php`
- [x] T011 [P] [US1] Unit テスト: CreateBookHandler 正常系 `backend/tests/Unit/Domain/Book/UseCases/CreateBookHandlerTest.php`

### Implementation for User Story 1

- [x] T012 [US1] CreateBookHandler に Book 生成・保存ロジックを実装 `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/CreateBookHandler.php`
- [x] T013 [US1] BookController.store() で CreateBookHandler を呼び出し `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`
- [x] T014 [US1] 登録成功時に 201 Created と BookResource を返却 `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`

**Checkpoint**: User Story 1 完了 - タイトル必須で蔵書登録が機能する

---

## Phase 4: User Story 2 - 登録時の入力検証 (Priority: P1)

**Goal**: 不正な入力に対して明確なエラーメッセージを返す

**Independent Test**: 無効な情報を入力した際に、適切なエラーメッセージが表示される

### Tests for User Story 2 ⚠️

- [x] T015 [P] [US2] Feature テスト: タイトル未入力で 422 エラー `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T016 [P] [US2] Feature テスト: 不正な ISBN 形式で 422 エラー `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T017 [P] [US2] Feature テスト: 出版年に非数値で 422 エラー `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T018 [P] [US2] Feature テスト: タイトル文字数超過（501文字）で 422 エラー `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T019 [P] [US2] Feature テスト: 出版年範囲外（0年、現在年+6）で 422 エラー `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T020 [P] [US2] Feature テスト: 空白のみのタイトルで 422 エラー `backend/tests/Feature/Book/CreateBookTest.php`

### Implementation for User Story 2

- [x] T021 [US2] CreateBookRequest に全バリデーションルールを実装 `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php`
- [x] T022 [US2] CreateBookRequest に日本語エラーメッセージを定義 `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php`
- [x] T023 [US2] ドメイン例外（EmptyBookTitleException, InvalidISBNException）を 422 に変換するハンドラ追加 `backend/app/Exceptions/Handler.php`

**Checkpoint**: User Story 2 完了 - 全バリデーションが機能し、明確なエラーメッセージを返す

---

## Phase 5: User Story 3 - 登録結果の確認 (Priority: P2)

**Goal**: 登録完了後に登録された図書の詳細情報を確認できる

**Independent Test**: 登録完了レスポンスに全項目が正しく含まれる

### Tests for User Story 3 ⚠️

- [x] T024 [P] [US3] Feature テスト: レスポンスに id が含まれる `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T025 [P] [US3] Feature テスト: レスポンスに全入力項目が含まれる `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T026 [P] [US3] Feature テスト: status が "available" で返る `backend/tests/Feature/Book/CreateBookTest.php`
- [x] T027 [P] [US3] Feature テスト: 登録後に検索 API で発見可能 `backend/tests/Feature/Book/CreateBookTest.php`

### Implementation for User Story 3

- [x] T028 [US3] BookResource が全項目を正しくフォーマットしていることを確認 `backend/packages/Domain/Book/Presentation/HTTP/Resources/BookResource.php`
- [x] T029 [US3] ISBN を正規化形式（ハイフンなし）で返却 `backend/packages/Domain/Book/Presentation/HTTP/Resources/BookResource.php`

**Checkpoint**: User Story 3 完了 - 登録結果が完全な形で確認可能

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: 品質向上とドキュメント整備

- [x] T030 [P] PHPDoc コメントを全新規ファイルに追加
- [x] T031 [P] Larastan で静的解析を実行し、エラーがないことを確認
- [x] T032 全テストを実行し、パスすることを確認 `docker compose exec backend php artisan test`
- [x] T033 quickstart.md の手順で動作確認

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - 全ユーザーストーリーをブロック
- **User Stories (Phase 3-5)**: Foundational 完了後に開始可能
  - US1 と US2 は同時優先度（P1）だが、US1 が基本機能のため先に実装推奨
  - US3 は US1 完了後に開始可能
- **Polish (Phase 6)**: 全ユーザーストーリー完了後

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能 - 他ストーリーへの依存なし
- **User Story 2 (P1)**: Foundational 完了後に開始可能 - US1 と並行可能だが、バリデーションは登録機能の拡張
- **User Story 3 (P2)**: US1 完了後に開始可能 - レスポンス確認は登録成功が前提

### Within Each User Story

- テストを先に書き、FAIL を確認
- 実装してテストを PASS
- ストーリー完了後に次の優先度へ

### Parallel Opportunities

- T008-T011: US1 のテストは並列実行可能
- T015-T020: US2 のテストは並列実行可能
- T024-T027: US3 のテストは並列実行可能
- T030-T031: Polish タスクは並列実行可能

---

## Parallel Example: User Story 1

```bash
# US1 のテストを並列で作成:
Task: "T008 Feature テスト: タイトルのみで登録成功"
Task: "T009 Feature テスト: 全項目入力で登録成功"
Task: "T010 Feature テスト: ISBN付きで登録成功"
Task: "T011 Unit テスト: CreateBookHandler 正常系"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup 完了
2. Phase 2: Foundational 完了（CRITICAL - 全ストーリーをブロック）
3. Phase 3: User Story 1 完了
4. **STOP and VALIDATE**: US1 を独立してテスト
5. デプロイ/デモ可能

### Incremental Delivery

1. Setup + Foundational → 基盤完了
2. User Story 1 → テスト → デプロイ/デモ (MVP!)
3. User Story 2 → テスト → デプロイ/デモ
4. User Story 3 → テスト → デプロイ/デモ
5. 各ストーリーが前のストーリーを壊さずに価値を追加

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベル = 特定のユーザーストーリーへのマッピング
- 各ユーザーストーリーは独立して完了・テスト可能
- テストが FAIL することを実装前に確認
- 各タスクまたは論理グループ後にコミット
- 任意のチェックポイントで停止してストーリーを独立検証可能
