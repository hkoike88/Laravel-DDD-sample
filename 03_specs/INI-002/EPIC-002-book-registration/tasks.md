# Tasks: 蔵書登録

**Input**: Design documents from `/specs/001-book-registration/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/openapi.yaml

**Tests**: テストはオプションとして含まれています（仕様書で明示的な要求なし）

**Organization**: ユーザーストーリーごとにタスクをグループ化し、独立した実装・テストを可能にしています。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: タスクが属するユーザーストーリー（US1, US2, US3, US4）
- ファイルパスを説明に含める

## Path Conventions

- **Backend**: `backend/packages/Domain/Book/`, `backend/database/migrations/`
- **Frontend**: `frontend/src/features/books/`

---

## Phase 1: Setup (共有インフラストラクチャ)

**Purpose**: データベーススキーマ拡張と基本設定

- [ ] T001 マイグレーションファイルを作成: `registered_by`, `registered_at` カラムを追加 in `backend/database/migrations/2026_01_06_000000_add_registration_columns_to_books_table.php`
- [ ] T002 マイグレーションを実行してスキーマを更新

---

## Phase 2: Foundational (ブロッキング前提条件)

**Purpose**: 全ユーザーストーリーの前提となるコアインフラストラクチャ

**CRITICAL**: このフェーズが完了するまでユーザーストーリーの作業は開始できません

- [ ] T003 BookRecord Eloquent モデルに `registered_by`, `registered_at` プロパティを追加 in `backend/packages/Domain/Book/Infrastructure/EloquentModels/BookRecord.php`
- [ ] T004 Book エンティティに `registeredBy`, `registeredAt` 属性を追加 in `backend/packages/Domain/Book/Domain/Model/Book.php`
- [ ] T005 [P] EloquentBookRepository の save メソッドで新規カラムを永続化するよう更新 in `backend/packages/Domain/Book/Application/Repositories/EloquentBookRepository.php`
- [ ] T006 [P] BookResource に `registered_by`, `registered_at` フィールドを追加 in `backend/packages/Domain/Book/Presentation/HTTP/Resources/BookResource.php`
- [ ] T007 CreateBookRequest の authorize メソッドで認証チェックを追加 in `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php`
- [ ] T008 routes.php で蔵書登録エンドポイントに認証ミドルウェアを適用 in `backend/packages/Domain/Book/Presentation/routes.php`

**Checkpoint**: 基盤準備完了 - ユーザーストーリー実装を開始可能

---

## Phase 3: User Story 1 + 2 - 基本的な蔵書登録 + バリデーション (Priority: P1)

**Goal**: 職員が図書情報を入力して登録でき、必須項目バリデーションが動作する

**Independent Test**: 職員が図書情報を入力し、登録ボタンを押すと図書がシステムに保存される。タイトル未入力時はエラーが表示される。

### Backend Implementation

- [ ] T009 [US1] CreateBookRequest のバリデーションルールを仕様に合わせて修正（タイトル200文字、著者100文字、出版社100文字、出版年1000〜現在年+1）in `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php`
- [ ] T010 [US1] CreateBookCommand に `staffId` パラメータを追加 in `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/CreateBookCommand.php`
- [ ] T011 [US1] CreateBookHandler で認証ユーザーを取得し、`registered_by`, `registered_at` を設定 in `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/CreateBookHandler.php`
- [ ] T012 [US1] BookController.store で認証ユーザーIDをコマンドに渡すよう修正 in `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`

### Frontend Implementation

- [ ] T013 [P] [US1] Book 型定義に `registered_by`, `registered_at` を追加 in `frontend/src/features/books/types/book.ts`
- [ ] T014 [P] [US1] CreateBookInput 型と IsbnCheckResponse 型を追加 in `frontend/src/features/books/types/book.ts`
- [ ] T015 [US1] Zod バリデーションスキーマを作成 in `frontend/src/features/books/schemas/bookRegistration.ts`
- [ ] T016 [US1] createBook API 関数を追加 in `frontend/src/features/books/api/bookApi.ts`
- [ ] T017 [US1] useBookRegistration フックを実装（TanStack Query mutation）in `frontend/src/features/books/hooks/useBookRegistration.ts`
- [ ] T018 [US1] BookRegistrationForm コンポーネントを実装（React Hook Form + Zod）in `frontend/src/features/books/components/BookRegistrationForm.tsx`
- [ ] T019 [US2] バリデーションエラー表示をフォームに実装（タイトル必須、文字数制限）in `frontend/src/features/books/components/BookRegistrationForm.tsx`
- [ ] T020 [US1] BookRegistrationPage を作成 in `frontend/src/features/books/pages/BookRegistrationPage.tsx`
- [ ] T021 [US1] ルーティングに `/books/new` を追加 in `frontend/src/app/router.tsx`

**Checkpoint**: US1 + US2 完了 - 基本登録とバリデーションが独立してテスト可能

---

## Phase 4: User Story 3 - ISBN重複チェック (Priority: P2)

**Goal**: ISBN入力時にリアルタイムで重複チェックを行い、警告を表示する

**Independent Test**: 既存ISBNを入力してフォーカスを移動すると警告が表示され、続行/中止を選択できる

### Backend Implementation

- [ ] T022 [P] [US3] CheckIsbnRequest を作成（ISBN形式バリデーション、認証チェック）in `backend/packages/Domain/Book/Presentation/HTTP/Requests/CheckIsbnRequest.php`
- [ ] T023 [US3] BookController に checkIsbn メソッドを追加 in `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`
- [ ] T024 [US3] routes.php に `GET /api/books/check-isbn` ルートを追加 in `backend/packages/Domain/Book/Presentation/routes.php`

### Frontend Implementation

- [ ] T025 [P] [US3] checkIsbnDuplicate API 関数を追加 in `frontend/src/features/books/api/bookApi.ts`
- [ ] T026 [US3] useIsbnCheck フックを実装（debounce付きリアルタイムチェック）in `frontend/src/features/books/hooks/useIsbnCheck.ts`
- [ ] T027 [US3] IsbnDuplicateWarning コンポーネントを作成（続行/中止ボタン）in `frontend/src/features/books/components/IsbnDuplicateWarning.tsx`
- [ ] T028 [US3] BookRegistrationForm に ISBN重複チェック機能を統合 in `frontend/src/features/books/components/BookRegistrationForm.tsx`

**Checkpoint**: US3 完了 - ISBN重複チェックが独立してテスト可能

---

## Phase 5: User Story 4 - 登録内容の確認 (Priority: P2)

**Goal**: 登録完了後に詳細確認画面を表示し、続けて登録できる

**Independent Test**: 登録完了後に確認画面が表示され、「続けて登録」で新規フォームに戻る

### Backend Implementation

- [ ] T029 [US4] BookController に show メソッドを追加（蔵書詳細取得）in `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php`
- [ ] T030 [US4] routes.php に `GET /api/books/{id}` ルートを追加（既存確認/新規追加）in `backend/packages/Domain/Book/Presentation/routes.php`

### Frontend Implementation

- [ ] T031 [P] [US4] getBook API 関数を追加 in `frontend/src/features/books/api/bookApi.ts`
- [ ] T032 [US4] BookCompletePage を作成（登録完了確認画面）in `frontend/src/features/books/pages/BookCompletePage.tsx`
- [ ] T033 [US4] 「続けて登録」ボタンと遷移ロジックを実装 in `frontend/src/features/books/pages/BookCompletePage.tsx`
- [ ] T034 [US4] ルーティングに `/books/:id/complete` を追加 in `frontend/src/app/router.tsx`
- [ ] T035 [US4] 登録成功後に確認画面へリダイレクトする処理を追加 in `frontend/src/features/books/hooks/useBookRegistration.ts`

**Checkpoint**: US4 完了 - 登録確認画面が独立してテスト可能

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: 複数ユーザーストーリーに影響する改善

- [ ] T036 [P] エラーハンドリングの統一（ネットワークエラー時の入力内容保持）in `frontend/src/features/books/components/BookRegistrationForm.tsx`
- [ ] T037 [P] ローディング状態の表示改善 in `frontend/src/features/books/components/BookRegistrationForm.tsx`
- [ ] T038 ナビゲーションメニューに「蔵書登録」リンクを追加 in `frontend/src/components/layout/`
- [ ] T039 quickstart.md の検証項目を実行して動作確認

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了に依存 - 全ユーザーストーリーをブロック
- **User Stories (Phase 3+)**: 全て Foundational フェーズ完了に依存
  - US1+US2 → US3 → US4 の順序で実装（優先度順）
  - または並列実装可能（チーム開発時）
- **Polish (Phase 6)**: 全ユーザーストーリー完了に依存

### User Story Dependencies

- **US1+US2 (P1)**: Foundational 完了後に開始可能 - 他ストーリーへの依存なし
- **US3 (P2)**: US1+US2 完了後に開始推奨（フォームへの統合のため）
- **US4 (P2)**: US1+US2 完了後に開始推奨（登録成功時の遷移のため）

### Within Each User Story

- バックエンド → フロントエンドの順序
- モデル/リクエスト → ハンドラ/コントローラ → UI の順序
- 各ストーリー完了後に独立テスト実施

### Parallel Opportunities

- T003, T004 は T005, T006, T007, T008 と並列実行可能
- フロントエンドの型定義（T013, T014）は並列実行可能
- バックエンドとフロントエンドの実装は、APIが固まれば並列可能
- US3 と US4 はバックエンド部分を並列実行可能

---

## Parallel Example: Phase 3 (US1+US2)

```bash
# バックエンド実装を並列実行（T009-T012 は依存関係あり、順次実行）

# フロントエンド型定義を並列実行:
Task: "Book 型定義に registered_by, registered_at を追加 in frontend/src/features/books/types/book.ts"
Task: "CreateBookInput 型と IsbnCheckResponse 型を追加 in frontend/src/features/books/types/book.ts"

# バックエンド完了後、フロントエンドの残りを順次実行
```

---

## Implementation Strategy

### MVP First (User Story 1+2 Only)

1. Phase 1: Setup を完了
2. Phase 2: Foundational を完了（CRITICAL - 全ストーリーをブロック）
3. Phase 3: User Story 1+2 を完了
4. **STOP and VALIDATE**: US1+US2 を独立テスト
5. 準備ができたらデプロイ/デモ

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. US1+US2 追加 → 独立テスト → デプロイ/デモ (MVP!)
3. US3 追加 → 独立テスト → デプロイ/デモ
4. US4 追加 → 独立テスト → デプロイ/デモ
5. 各ストーリーが既存機能を壊さずに価値を追加

### Task Count Summary

| Phase | タスク数 |
|-------|---------|
| Phase 1: Setup | 2 |
| Phase 2: Foundational | 6 |
| Phase 3: US1+US2 (P1) | 13 |
| Phase 4: US3 (P2) | 7 |
| Phase 5: US4 (P2) | 7 |
| Phase 6: Polish | 4 |
| **Total** | **39** |

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルはタスクを特定のユーザーストーリーにマッピング
- 各ユーザーストーリーは独立して完了・テスト可能
- タスク完了後またはロジカルグループ後にコミット
- 任意のチェックポイントで停止してストーリーを独立検証可能
- 回避: 曖昧なタスク、同一ファイルの競合、独立性を壊すクロスストーリー依存
