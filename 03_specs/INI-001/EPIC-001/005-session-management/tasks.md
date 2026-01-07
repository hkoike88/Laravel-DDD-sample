# Tasks: セッション管理実装

**Input**: Design documents from `/specs/001-session-management/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: テストタスクは Feature テストとして含まれています（仕様書の成果物に記載）。

**Organization**: タスクはユーザーストーリー単位で整理され、各ストーリーを独立して実装・テストできます。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 並列実行可能（異なるファイル、依存関係なし）
- **[Story]**: このタスクが属するユーザーストーリー（US1, US2, US3, US4）
- 説明には正確なファイルパスを含める

## Path Conventions

- **Backend**: `backend/`（Laravel アプリケーション）
- **Frontend**: `frontend/`（React アプリケーション）
- **Tests**: `backend/tests/`, `frontend/tests/`

---

## Phase 1: Setup（共有インフラ）

**Purpose**: プロジェクト初期化と基本構造

- [x] T001 現在のブランチが `001-session-management` であることを確認
- [x] T002 [P] バックエンドの依存関係を確認（`docker compose exec backend composer install`）
- [x] T003 [P] フロントエンドの依存関係を確認（`docker compose exec frontend npm install`）

---

## Phase 2: Foundational（基盤整備）

**Purpose**: すべてのユーザーストーリーに必要な共通基盤

**⚠️ CRITICAL**: このフェーズが完了するまで、ユーザーストーリーの実装は開始できません

### データベース変更

- [ ] T004 staffs テーブルに is_admin カラムを追加するマイグレーションを作成 `backend/database/migrations/xxxx_add_is_admin_to_staffs_table.php`
- [ ] T005 マイグレーションを実行（`docker compose exec backend php artisan migrate`）

### ドメインモデル更新

- [ ] T006 [P] Staff エンティティに isAdmin プロパティと isAdmin() メソッドを追加 `backend/packages/Domain/Staff/Domain/Model/Staff.php`
- [ ] T007 [P] StaffRecord に is_admin カラム対応を追加 `backend/packages/Domain/Staff/Infrastructure/EloquentModels/StaffRecord.php`
- [ ] T008 Staff エンティティの単体テストを更新（isAdmin メソッドのテスト追加） `backend/tests/Unit/Domain/Staff/Model/StaffTest.php`

### セッション基本設定

- [ ] T009 config/session.php を更新（driver=database, lifetime=30, encrypt=true, secure=true, http_only=true, same_site=lax） `backend/config/session.php`

**Checkpoint**: 基盤準備完了 - ユーザーストーリーの実装を開始できます

---

## Phase 3: User Story 1 - セッション自動タイムアウト (Priority: P1) 🎯 MVP

**Goal**: 30分のアイドルタイムアウトと8時間の絶対タイムアウトを実装し、セキュリティを確保する

**Independent Test**: ログイン後30分間操作しないとセッションが失効し、再ログインを求められることを確認

### バックエンド実装

- [ ] T010 [US1] AbsoluteSessionTimeout ミドルウェアを作成 `backend/app/Http/Middleware/AbsoluteSessionTimeout.php`
  - セッション作成日時を session_created_at として記録
  - 8時間経過後に強制ログアウト
  - 401 レスポンスでタイムアウトメッセージを返す
- [ ] T011 [US1] bootstrap/app.php に AbsoluteSessionTimeout ミドルウェアを登録
- [ ] T012 [US1] ログイン成功時に session_created_at を設定するよう LoginController を更新 `backend/app/Http/Controllers/Api/Staff/Auth/LoginController.php`

### フロントエンド実装

- [ ] T013 [P] [US1] Axios インターセプターにセッションタイムアウト処理を追加 `frontend/src/lib/axios.ts`
  - 401 レスポンスを検知
  - メッセージに「セッション」が含まれる場合はタイムアウトメッセージを表示
  - authStore をクリアしてログイン画面へリダイレクト
- [ ] T014 [P] [US1] authStore に clearUser メソッドがあることを確認（なければ追加） `frontend/src/stores/authStore.ts`

### テスト

- [ ] T015 [US1] セッションタイムアウトの Feature テストを作成 `backend/tests/Feature/Auth/SessionTimeoutTest.php`
  - アイドルタイムアウト（30分）のテスト
  - 絶対タイムアウト（8時間）のテスト
  - タイムアウト前の正常動作テスト

**Checkpoint**: セッション自動タイムアウト機能が完成し、独立してテスト可能

---

## Phase 4: User Story 2 - 同時ログイン制限 (Priority: P2)

**Goal**: 一般職員は最大3台、管理者は最大1台の同時ログインに制限する

**Independent Test**: 一般職員が4台目の端末からログインした際に、最も古いセッションが自動的に終了されることを確認

### バックエンド実装

- [ ] T016 [US2] ConcurrentSessionLimit ミドルウェアを作成 `backend/app/Http/Middleware/ConcurrentSessionLimit.php`
  - ユーザーの is_admin フラグに基づいて上限を決定（一般職員: 3、管理者: 1）
  - sessions テーブルで同一ユーザーのセッション数を確認
  - 上限超過時は last_activity が最も古いセッションを削除
- [ ] T017 [US2] bootstrap/app.php に ConcurrentSessionLimit ミドルウェアを登録（ログイン後のルートグループに適用）
- [ ] T018 [US2] ログイン処理後に ConcurrentSessionLimit が実行されるよう調整 `backend/app/Http/Controllers/Api/Staff/Auth/LoginController.php`

### テスト

- [ ] T019 [US2] 同時ログイン制限の Feature テストを作成 `backend/tests/Feature/Auth/ConcurrentSessionTest.php`
  - 一般職員の3台ログイン成功テスト
  - 一般職員の4台目ログインで最古セッション削除テスト
  - 管理者の1台ログイン成功テスト
  - 管理者の2台目ログインで既存セッション削除テスト

**Checkpoint**: 同時ログイン制限機能が完成し、独立してテスト可能

---

## Phase 5: User Story 3 - セッション永続化 (Priority: P3)

**Goal**: セッション情報をデータベースに永続化し、サーバー障害やスケールアウト時にもセッション情報を維持する

**Independent Test**: サーバー再起動後も、有効なセッションを持つ職員が再ログインなしで操作を継続できることを確認

### バックエンド確認・設定

- [ ] T020 [US3] sessions テーブルが存在し、user_id が ULID 対応であることを確認 `backend/database/migrations/`
- [ ] T021 [US3] SESSION_DRIVER=database が .env に設定されていることを確認 `backend/.env`

### テスト

- [ ] T022 [US3] セッション永続化の Feature テストを作成 `backend/tests/Feature/Auth/SessionPersistenceTest.php`
  - セッションがデータベースに保存されることのテスト
  - セッション情報の取得テスト

**Checkpoint**: セッション永続化機能が完成し、独立してテスト可能

---

## Phase 6: User Story 4 - CSRF 保護 (Priority: P4)

**Goal**: CSRF 攻撃からシステムを保護する

**Independent Test**: 有効な CSRF トークンなしでフォーム送信を試みた際に、リクエストが拒否されることを確認

### バックエンド確認

- [ ] T023 [US4] Laravel Sanctum の CSRF 保護が有効であることを確認 `backend/config/sanctum.php`
- [ ] T024 [US4] VerifyCsrfToken ミドルウェアが適切に設定されていることを確認 `backend/bootstrap/app.php`

### フロントエンド確認

- [ ] T025 [P] [US4] Axios で withCredentials: true が設定されていることを確認 `frontend/src/lib/axios.ts`
- [ ] T026 [P] [US4] ログイン前に /sanctum/csrf-cookie を呼び出すことを確認 `frontend/src/features/auth/api/authApi.ts`

### テスト

- [ ] T027 [US4] CSRF 保護の Feature テストを作成 `backend/tests/Feature/Auth/CsrfProtectionTest.php`
  - CSRF トークンありでのリクエスト成功テスト
  - CSRF トークンなしでのリクエスト拒否テスト

**Checkpoint**: CSRF 保護機能が確認済み

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: 全ユーザーストーリーに影響する改善

- [ ] T028 [P] すべてのミドルウェアに PHPDoc コメントを追加
- [ ] T029 [P] フロントエンドのセッションタイムアウト処理に JSDoc コメントを追加
- [ ] T030 全テストの実行と確認（`docker compose exec backend php artisan test`）
- [ ] T031 Larastan による静的解析の実行（`docker compose exec backend vendor/bin/phpstan analyse`）
- [ ] T032 quickstart.md に従って動作確認を実施

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1: Setup
    ↓
Phase 2: Foundational（基盤整備）
    ↓ (ブロッキング)
    ├── Phase 3: User Story 1 (P1) 🎯 MVP
    ├── Phase 4: User Story 2 (P2)
    ├── Phase 5: User Story 3 (P3)
    └── Phase 6: User Story 4 (P4)
          ↓
    Phase 7: Polish
```

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完了後に開始可能。他のストーリーに依存しない
- **User Story 2 (P2)**: Foundational 完了後に開始可能。is_admin が必要（Foundational で追加済み）
- **User Story 3 (P3)**: Foundational 完了後に開始可能。他のストーリーに依存しない
- **User Story 4 (P4)**: Foundational 完了後に開始可能。他のストーリーに依存しない

### Within Each User Story

1. バックエンド実装 → フロントエンド実装 → テスト
2. ミドルウェア作成 → ミドルウェア登録 → コントローラー調整
3. コア実装 → テスト作成

### Parallel Opportunities

**Phase 2 内の並列実行**:
```bash
# 並列実行可能:
Task: T006 "Staff エンティティ更新"
Task: T007 "StaffRecord 更新"
```

**Phase 3 内の並列実行**:
```bash
# バックエンド実装後、フロントエンドは並列可能:
Task: T013 "Axios インターセプター更新"
Task: T014 "authStore 確認"
```

**ユーザーストーリー間の並列実行**:
```bash
# Foundational 完了後、異なる開発者で並列作業可能:
Developer A: Phase 3 (User Story 1)
Developer B: Phase 4 (User Story 2)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup を完了
2. Phase 2: Foundational を完了（**必須**）
3. Phase 3: User Story 1 を完了
4. **STOP and VALIDATE**: セッションタイムアウト機能を独立してテスト
5. 必要であればデプロイ/デモ

### Incremental Delivery

1. Setup + Foundational 完了 → 基盤準備完了
2. User Story 1 追加 → 独立テスト → デプロイ/デモ（MVP!）
3. User Story 2 追加 → 独立テスト → デプロイ/デモ
4. User Story 3 追加 → 独立テスト → デプロイ/デモ
5. User Story 4 追加 → 独立テスト → デプロイ/デモ

### Suggested MVP Scope

**User Story 1（セッション自動タイムアウト）** のみで MVP として十分に価値がある：
- アイドルタイムアウト（30分）
- 絶対タイムアウト（8時間）
- セキュリティの根幹機能

---

## Notes

- [P] タスク = 異なるファイル、依存関係なし
- [Story] ラベル = タスクをユーザーストーリーに紐付けてトレーサビリティを確保
- 各ユーザーストーリーは独立して完了・テスト可能
- テストが失敗することを確認してから実装
- 各タスクまたは論理グループの完了後にコミット
- 任意のチェックポイントで停止してストーリーを独立検証可能
