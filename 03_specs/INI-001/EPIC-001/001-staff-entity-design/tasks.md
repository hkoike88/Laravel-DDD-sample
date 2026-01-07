# Tasks: 職員エンティティの設計

**Input**: Design documents from `/specs/001-staff-entity-design/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テストタスクを含む（spec.md の受け入れシナリオに基づく）

**Organization**: タスクはユーザーストーリーごとにグループ化され、各ストーリーを独立して実装・テスト可能

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: どのユーザーストーリーに属するか（例: US1, US2, US3, US4）
- 説明には正確なファイルパスを含める

## Path Conventions

- **Backend**: `backend/packages/Domain/Staff/`
- **Tests**: `backend/tests/Unit/Packages/Domain/Staff/`
- **Migrations**: `backend/database/migrations/`

---

## Phase 1: Setup（共有インフラストラクチャ）

**Purpose**: Staff ドメインのディレクトリ構造と基本設定

- [x] T001 Staff ドメインのディレクトリ構造を作成（backend/packages/Domain/Staff/{Domain,Application,Infrastructure}）
- [x] T002 [P] ドメイン例外の基底クラスを作成 in backend/packages/Domain/Staff/Domain/Exceptions/
- [x] T003 [P] StaffServiceProvider を作成 in backend/packages/Domain/Staff/Application/Providers/StaffServiceProvider.php
- [x] T004 StaffServiceProvider を bootstrap/providers.php に登録

---

## Phase 2: Foundational（ブロッキング前提条件）

**Purpose**: すべてのユーザーストーリーに必要な基盤コンポーネント

**⚠️ CRITICAL**: このフェーズが完了するまでユーザーストーリーの実装は開始できません

- [x] T005 staffs テーブルのマイグレーションを作成 in backend/database/migrations/2025_01_01_000000_create_staffs_table.php
- [x] T006 マイグレーションを実行してテーブルを作成
- [x] T007 [P] StaffRecord Eloquent モデルを作成 in backend/packages/Domain/Staff/Infrastructure/EloquentModels/StaffRecord.php
- [x] T008 [P] StaffRepositoryInterface を定義 in backend/packages/Domain/Staff/Domain/Repositories/StaffRepositoryInterface.php
- [x] T009 EloquentStaffRepository を実装 in backend/packages/Domain/Staff/Application/Repositories/EloquentStaffRepository.php
- [x] T010 StaffServiceProvider にリポジトリバインディングを追加

**Checkpoint**: 基盤準備完了 - ユーザーストーリーの実装を並列で開始可能

---

## Phase 3: User Story 1 - 職員データの永続化 (Priority: P1) 🎯 MVP

**Goal**: 職員情報（ID、メールアドレス、パスワード、名前）をシステムに保存できるようにする

**Independent Test**: 職員情報を新規登録し、その情報がデータベースに正しく保存されることを確認できる

### Tests for User Story 1

- [ ] T011 [P] [US1] StaffId 値オブジェクトのユニットテストを作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/ValueObjects/StaffIdTest.php
- [ ] T012 [P] [US1] Staff エンティティのユニットテスト（create, reconstruct）を作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/Model/StaffTest.php
- [ ] T013 [P] [US1] EloquentStaffRepository の統合テストを作成 in backend/tests/Unit/Packages/Domain/Staff/Application/Repositories/EloquentStaffRepositoryTest.php

### Implementation for User Story 1

- [ ] T014 [P] [US1] StaffId 値オブジェクトを実装 in backend/packages/Domain/Staff/Domain/ValueObjects/StaffId.php
- [ ] T015 [P] [US1] StaffName 値オブジェクトを実装 in backend/packages/Domain/Staff/Domain/ValueObjects/StaffName.php
- [ ] T016 [P] [US1] StaffNotFoundException を実装 in backend/packages/Domain/Staff/Domain/Exceptions/StaffNotFoundException.php
- [ ] T017 [US1] Staff エンティティ（create, reconstruct, getters）を実装 in backend/packages/Domain/Staff/Domain/Model/Staff.php
- [ ] T018 [US1] EloquentStaffRepository に find, findOrNull, save を実装

**Checkpoint**: User Story 1 が完全に機能し、独立してテスト可能

---

## Phase 4: User Story 2 - パスワードのセキュアな管理 (Priority: P1)

**Goal**: 職員のパスワードを安全にハッシュ化して保存し、検証機能を提供する

**Independent Test**: パスワードを設定した職員を保存し、保存されたパスワードがハッシュ化されていること、かつ元のパスワードで検証できることを確認できる

### Tests for User Story 2

- [ ] T019 [P] [US2] Password 値オブジェクトのユニットテスト（ハッシュ化、検証、長さ制限）を作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/ValueObjects/PasswordTest.php
- [ ] T020 [P] [US2] Staff エンティティのパスワード検証テストを追加 in backend/tests/Unit/Packages/Domain/Staff/Domain/Model/StaffTest.php

### Implementation for User Story 2

- [ ] T021 [P] [US2] InvalidPasswordException を実装 in backend/packages/Domain/Staff/Domain/Exceptions/InvalidPasswordException.php
- [ ] T022 [US2] Password 値オブジェクト（fromPlainText, fromHash, verify）を実装 in backend/packages/Domain/Staff/Domain/ValueObjects/Password.php
- [ ] T023 [US2] Staff エンティティに verifyPassword メソッドを追加

**Checkpoint**: User Story 1 AND 2 が両方とも独立して動作

---

## Phase 5: User Story 3 - アカウントロック状態の管理 (Priority: P2)

**Goal**: 職員アカウントのロック・アンロック機能とログイン失敗回数の管理

**Independent Test**: 職員アカウントをロックし、ロック状態とロック日時が正しく記録されることを確認できる

### Tests for User Story 3

- [ ] T024 [P] [US3] Staff エンティティのロック機能テスト（lock, unlock, incrementFailedLoginAttempts）を作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/Model/StaffTest.php

### Implementation for User Story 3

- [ ] T025 [US3] Staff エンティティに lock メソッドを追加（isLocked=true, lockedAt=now）
- [ ] T026 [US3] Staff エンティティに unlock メソッドを追加（isLocked=false, lockedAt=null, failedLoginAttempts=0）
- [ ] T027 [US3] Staff エンティティに incrementFailedLoginAttempts メソッドを追加
- [ ] T028 [US3] Staff エンティティに resetFailedLoginAttempts メソッドを追加
- [ ] T029 [US3] Staff エンティティに isLocked getter を追加

**Checkpoint**: User Story 1, 2, 3 がすべて独立して機能

---

## Phase 6: User Story 4 - 入力値の妥当性検証 (Priority: P2)

**Goal**: 職員情報の入力値バリデーション（メール形式、パスワード長、名前、重複チェック）

**Independent Test**: 不正な形式のメールアドレスで職員を作成しようとした場合にエラーが発生することを確認できる

### Tests for User Story 4

- [ ] T030 [P] [US4] Email 値オブジェクトのユニットテスト（形式検証、正規化、最大長）を作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/ValueObjects/EmailTest.php
- [ ] T031 [P] [US4] StaffName 値オブジェクトのユニットテスト（空文字、最大長、制御文字除去）を作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/ValueObjects/StaffNameTest.php
- [ ] T032 [P] [US4] EloquentStaffRepository の重複チェックテストを作成 in backend/tests/Unit/Packages/Domain/Staff/Application/Repositories/EloquentStaffRepositoryTest.php

### Implementation for User Story 4

- [ ] T033 [P] [US4] InvalidEmailException を実装 in backend/packages/Domain/Staff/Domain/Exceptions/InvalidEmailException.php
- [ ] T034 [P] [US4] DuplicateEmailException を実装 in backend/packages/Domain/Staff/Domain/Exceptions/DuplicateEmailException.php
- [ ] T035 [P] [US4] InvalidStaffNameException を実装 in backend/packages/Domain/Staff/Domain/Exceptions/InvalidStaffNameException.php
- [ ] T036 [US4] Email 値オブジェクト（create, バリデーション、小文字正規化）を実装 in backend/packages/Domain/Staff/Domain/ValueObjects/Email.php
- [ ] T037 [US4] StaffName 値オブジェクトにバリデーション（空文字チェック、100文字制限、制御文字除去）を追加
- [ ] T038 [US4] EloquentStaffRepository に existsByEmail, findByEmail を実装
- [ ] T039 [US4] EloquentStaffRepository の save で重複チェックを追加

**Checkpoint**: すべてのユーザーストーリーが独立して機能

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: 複数のユーザーストーリーに影響する改善

- [ ] T040 [P] PHPDoc コメントをすべてのクラスに追加
- [ ] T041 [P] Larastan（PHPStan）でコード品質チェックを実行
- [ ] T042 テストカバレッジを確認（Domain 90%+、Repository 80%+）
- [ ] T043 quickstart.md に従って動作確認を実施
- [ ] T044 [P] コードフォーマット（Laravel Pint）を適用

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 依存なし - 即座に開始可能
- **Foundational (Phase 2)**: Setup 完了後 - すべてのユーザーストーリーをブロック
- **User Stories (Phase 3-6)**: Foundational 完了後に開始可能
  - 各ユーザーストーリーは並列で進行可能（複数人の場合）
  - または優先順位順に順次実行（P1 → P2）
- **Polish (Phase 7)**: すべてのユーザーストーリー完了後

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後 - 他のストーリーに依存しない（MVP）
- **User Story 2 (P1)**: Foundational 完了後 - US1 の Staff エンティティに統合
- **User Story 3 (P2)**: Foundational 完了後 - US1 の Staff エンティティに統合
- **User Story 4 (P2)**: Foundational 完了後 - US1 の値オブジェクトを拡張

### Within Each User Story

- テストを先に作成し、失敗することを確認
- 値オブジェクト → エンティティ → リポジトリの順で実装
- コア実装 → 統合の順で進める
- 次の優先度に移る前にストーリーを完了

### Parallel Opportunities

- [P] マークのセットアップタスクは並列実行可能
- [P] マークの Foundational タスクは Phase 2 内で並列実行可能
- Foundational 完了後、すべてのユーザーストーリーを並列開始可能
- 各ストーリー内の [P] マークのテストは並列実行可能
- 各ストーリー内の [P] マークの値オブジェクトは並列実行可能

---

## Parallel Example: User Story 1

```bash
# User Story 1 のすべてのテストを並列起動:
Task: "StaffId 値オブジェクトのユニットテストを作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/ValueObjects/StaffIdTest.php"
Task: "Staff エンティティのユニットテストを作成 in backend/tests/Unit/Packages/Domain/Staff/Domain/Model/StaffTest.php"
Task: "EloquentStaffRepository の統合テストを作成 in backend/tests/Unit/Packages/Domain/Staff/Application/Repositories/EloquentStaffRepositoryTest.php"

# User Story 1 のすべての値オブジェクトを並列起動:
Task: "StaffId 値オブジェクトを実装 in backend/packages/Domain/Staff/Domain/ValueObjects/StaffId.php"
Task: "StaffName 値オブジェクトを実装 in backend/packages/Domain/Staff/Domain/ValueObjects/StaffName.php"
Task: "StaffNotFoundException を実装 in backend/packages/Domain/Staff/Domain/Exceptions/StaffNotFoundException.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup を完了
2. Phase 2: Foundational を完了（CRITICAL - すべてのストーリーをブロック）
3. Phase 3: User Story 1 を完了
4. **STOP and VALIDATE**: User Story 1 を独立してテスト
5. 準備ができたらデプロイ/デモ

### Incremental Delivery

1. Setup + Foundational を完了 → 基盤準備完了
2. User Story 1 を追加 → 独立してテスト → デプロイ/デモ（MVP!）
3. User Story 2 を追加 → 独立してテスト → デプロイ/デモ
4. User Story 3 を追加 → 独立してテスト → デプロイ/デモ
5. User Story 4 を追加 → 独立してテスト → デプロイ/デモ
6. 各ストーリーは以前のストーリーを壊さずに価値を追加

### Parallel Team Strategy

複数の開発者がいる場合:

1. チームで Setup + Foundational を完了
2. Foundational 完了後:
   - Developer A: User Story 1
   - Developer B: User Story 2
3. ストーリーは独立して完了・統合

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベルはタスクを特定のユーザーストーリーにマッピング
- 各ユーザーストーリーは独立して完了・テスト可能
- 実装前にテストが失敗することを確認
- 各タスクまたは論理グループの後にコミット
- 任意のチェックポイントで停止してストーリーを独立して検証可能
- 避けるべき: 曖昧なタスク、同一ファイルの競合、独立性を損なうストーリー間依存
